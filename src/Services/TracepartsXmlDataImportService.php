<?php

namespace Amplify\System\Services;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\CategoryProduct;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductImage;
use Amplify\System\Backend\Models\SkuProduct;
use Amplify\System\Backend\Models\User;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Jobs\ImportTracePartsXmlDataSkuChunkJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TracepartsXmlDataImportService
{
    protected string $xml;

    public function __construct()
    {
        $path = public_path('assets/cnid_7673_cat_1001.xml');
        if (! file_exists($path)) {
            Log::info("XML not found at {$path}");

            return;
        }
        $this->xml = file_get_contents($path);
    }

    /**
     * Return the currently authenticated Backpack user id,
     * or look up a fallback by e-mail (config/backpack.php → import_user_email).
     */
    private function getImportUserId(): int
    {
        if ($id = backpack_auth()->id()) {
            return $id;
        }

        $email = 'raitken@easyask.com';

        return User::where('email', $email)->value('id')
               ?? throw new \RuntimeException("No import user found for email “{$email}”");
    }

    public function importAttributes(): array
    {
        $attrs = UtilityHelper::extractStructuredAttributes($this->xml);
        $created = $updated = 0;

        foreach ($attrs as $a) {
            $query = Attribute::where('traceparts_attribute_id', $a['id']);
            $translations = [
                \App::getLocale() => $a['name'],
            ];

            if ($query->exists()) {
                $query->update([
                    'name' => $translations,
                    'type' => $a['type'],
                    'unit' => $a['unit'] ?? null,
                ]);
                $updated++;
            } else {
                Attribute::create([
                    'traceparts_attribute_id' => $a['id'],
                    'name' => $translations,
                    'type' => $a['type'],
                    'unit' => $a['unit'] ?? null,
                ]);
                $created++;
            }
        }

        return [$created, $updated];
    }

    public function importCategories(): array
    {
        $cats = UtilityHelper::extractStructuredCategories($this->xml);
        $created = $updated = 0;

        foreach ($cats as $cat) {
            $query = Category::where('traceparts_category_id', $cat['category_id']);
            $catalogId = config('amplify.sayt.default_catalog') ?? null;
            // Generate unique category code and slug
            $code = UtilityHelper::generateUniqueCategoryCodeOrSlug($cat['category_id'], $cat['category_code']);
            $slug = UtilityHelper::generateUniqueCategoryCodeOrSlug($cat['category_id'], $cat['category_slug'], 'slug');

            // Handle parent_id for categories
            if (empty($cat['parent_id'])) {
                $cat['parent_id'] = null;
            } else {
                $parentCategory = Category::where('traceparts_category_id', $cat['parent_id'])->first();
                $cat['parent_id'] = $parentCategory ? $parentCategory->id : null;
            }

            if ($query->exists()) {
                // Check if the slug needs to be updated
                $existingCategory = $query->first();
                if ($existingCategory->category_slug !== $cat['category_slug']) {
                    $cat['category_slug'] = $slug;
                }

                // Update the existing category
                $query->update([
                    'category_name' => ['en' => $cat['category_name']],
                    'category_slug' => $cat['category_slug'],
                    'category_code' => UtilityHelper::cleanCategoryCode($existingCategory->category_code),
                    'parent_id' => empty($cat['parent_id']) ? $catalogId : $cat['parent_id'],
                    'image' => $cat['image'],
                ]);
                $updated++;
            } else {
                Category::create([
                    'traceparts_category_id' => $cat['category_id'],
                    'category_code' => UtilityHelper::cleanCategoryCode($code),
                    'category_name' => $cat['category_name'],
                    'category_slug' => $slug,
                    'parent_id' => empty($cat['parent_id']) ? $catalogId : $cat['parent_id'],
                    'image' => $cat['image'],
                ]);
                $created++;
            }
        }

        return [$created, $updated];
    }

    public function importMasterProducts(): array
    {
        $prods = UtilityHelper::extractStructuredMasterProducts($this->xml);
        $created = $updated = $skipped = 0;
        $userId = $this->getImportUserId();

        foreach ($prods as $p) {
            $existingSkus = Product::whereIn('product_code', $p['sku_product_codes'])->count();
            $firstSku = Product::whereIn('product_code', $p['sku_product_codes'])->with('productImage')->first();

            if ($existingSkus > 0) {
                $product = $this->updateProductData($p, $userId);

                Product::whereIn('product_code', $p['sku_product_codes'])
                    ->where('product_code', '!=', $product['product_code'])
                    ->update([
                        'parent_id' => $product->id,
                        'sku_id' => DB::raw("CONCAT_WS('-', {$product->id}, id)"),
                    ]);

                if (! empty($firstSku->productImage->main)) {
                    // Create or update product image
                    ProductImage::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'main' => $firstSku->productImage->main,
                        ]
                    );
                }

                // Tally created vs updated
                if ($product->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        }

        return [$created, $updated, $skipped];
    }

    public function attachMasterProductsToCategories(): array
    {
        // Step 1: Get child -> parent mapping
        $parentProducts = Product::whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->pluck('parent_id', 'id')
            ->toArray(); // [child_id => parent_id]

        // Step 2: Get category_ids grouped by child_id, pick latest (sorted)
        $categoryProducts = CategoryProduct::whereIn('product_id', array_keys($parentProducts))
            ->select('product_id', 'category_id')
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->pluck('category_id')->sort()->last();
            })
            ->toArray(); // [child_id => latest_category_id]

        // Step 3: Build the data for parent products
        $now = Carbon::now();
        $categoryProductData = [];

        foreach ($categoryProducts as $childId => $categoryId) {
            $parentId = $parentProducts[$childId];
            $categoryProductData[] = [
                'product_id' => $parentId,
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Step 4: Prepare for insert/update
        $existing = CategoryProduct::whereIn('product_id', collect($categoryProductData)->pluck('product_id'))
            ->select('product_id', 'category_id')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->product_id.'|'.$item->category_id => true]);

        $toInsert = [];
        $toUpdate = [];

        foreach ($categoryProductData as $row) {
            $key = $row['product_id'].'|'.$row['category_id'];
            if (isset($existing[$key])) {
                $toUpdate[] = ['product_id' => $row['product_id'], 'category_id' => $row['category_id'], 'updated_at' => $now];
            } else {
                $toInsert[] = $row;
            }
        }

        // Step 5: Insert new ones
        if (! empty($toInsert)) {
            CategoryProduct::insert($toInsert);
        }

        // Step 6: Update existing ones
        if (! empty($toUpdate)) {
            CategoryProduct::upsert($toUpdate, ['product_id', 'category_id'], ['updated_at']);
        }

        // Step 7: Counts
        $insertCount = count($toInsert);
        $updateCount = count($toUpdate);

        return [$insertCount, $updateCount];
    }

    public function importSkuProductsWithAllData(): array
    {
        $filePath = public_path('assets/cnid_7673_cat_1001.xml');
        $generator = UtilityHelper::streamSkuItems($filePath);

        $chunks = [];
        $jobCount = 0;
        $totalSkus = 0;

        foreach ($generator as $sku) {
            $totalSkus++;
            $chunks[] = $sku;

            if (count($chunks) === 500) {
                dispatch(new ImportTracePartsXmlDataSkuChunkJob($chunks));
                $jobCount++;
                $chunks = [];
            }
        }
        if (count($chunks)) {
            dispatch(new ImportTracePartsXmlDataSkuChunkJob($chunks));
            $jobCount++;
        }

        return [$totalSkus, $jobCount];
    }

    public function updateSkuId(): array
    {
        $products = Product::whereNotNull('parent_id')->get()->map(function ($product) {
            return [
                'parent_id' => $product->parent_id,
                'sku_id' => $product->id,
            ];
        })->toArray();

        $dbSkuProducts = SkuProduct::get();

        $newSkuProducts = [];

        foreach ($products as $skuProduct) {
            $exists = $dbSkuProducts->where('sku_id', $skuProduct['sku_id'])
                ->where('parent_id', $skuProduct['parent_id'])
                ->first();
            if (empty($exists)) {
                $newSkuProducts[] = $skuProduct;
            }
        }

        SkuProduct::insert($newSkuProducts);

        return [$newSkuProducts];
    }

    private function updateProductData($p, $userId)
    {
        $existingProduct = Product::where('product_code', $p['product_code'])
            ->first();

        $attributeIds = Attribute::whereIn('traceparts_attribute_id', $p['sku_default_attributes'])
            ->get()
            ->pluck('id')
            ->toArray();

        if ($existingProduct) {
            $existingProduct->update([
                'sku_default_attributes' => json_encode($attributeIds),
                'has_sku' => true,
                'user_id' => $userId,
            ]);

            return $existingProduct;
        }

        // Update or Create new product by product_code
        return Product::Create(
            [
                'product_code' => strtoupper($p['product_code']),
                'product_name' => strtoupper($p['product_name']),
                'sku_default_attributes' => json_encode($p['sku_default_attributes']),
                'has_sku' => true,
                'user_id' => $userId,
            ]
        );
    }
}
