<?php

namespace Amplify\System\Backend\Services;

use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Jobs\ImportTracepartsXmlDataSkuChunkJob;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
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
            $catalogId = config('amplify.search.default_catalog') ?? null;
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
            $existingProduct = Product::whereNull('traceparts_product_id')
                ->where('product_code', $p['product_code'])
                ->first();
            if ($existingProduct) {
                $skipped++;
                Log::info("Skipping product with code {$p['product_code']} as it already exists without traceparts_product_id.");

                continue;
            }

            // Upsert the product by traceparts_product_id
            $product = Product::updateOrCreate(
                ['traceparts_product_id' => $p['id']],
                [
                    'product_code' => $p['product_code'],
                    'product_name' => $p['product_name'],
                    'sku_default_attributes' => json_encode($p['sku_default_attributes']),
                    'has_sku' => true,
                    'user_id' => $userId,
                ]
            );

            // Tally created vs updated
            if ($product->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            // Upsert the image using the real PK
            if (! empty($p['image'])) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id],
                    ['main' => $p['image']]
                );
            }
        }

        return [$created, $updated, $skipped];
    }

    public function attachMasterProductsToCategories(): array
    {
        $res = UtilityHelper::attachProductsToCategories($this->xml);

        return [$res['attached'], $res['skipped']];
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
                dispatch(new ImportTracepartsXmlDataSkuChunkJob($chunks));
                $jobCount++;
                $chunks = [];
            }
        }

        if (count($chunks)) {
            dispatch(new ImportTracepartsXmlDataSkuChunkJob($chunks));
            $jobCount++;
        }

        return [$totalSkus, $jobCount];
    }

    public function updateSkuId(): array
    {
        $products = Product::whereNotNull('parent_id')->where('has_sku', false)->update([
            'sku_id' => DB::raw("CONCAT_WS('-', parent_id, id)"),
        ]);
        Log::info('Updated sku_id', [$products]);

        return [$products];
    }
}
