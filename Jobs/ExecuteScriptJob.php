<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Services\JobFailService;
use Amplify\System\Utility\Models\DataTransformationError;
use Amplify\System\Utility\Services\DataTransformation\ExecuteScriptService;
use App\Models\Attribute;
use App\Models\AttributeProduct;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Product;
use App\Models\ProductClassification;
use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dataTransformation;

    public $productData;

    public $request;

    public $appliesTo;

    public $scriptType;

    public $userId;

    public $isAnyCategory;

    public $isSkuProduct;

    public function __construct($productData, $data, $dataTransformation)
    {
        $this->dataTransformation = $dataTransformation;
        $this->productData = $productData;
        $this->request = [
            'script' => $data['scriptsArr'],
            'fields' => $this->getProductFields(),
            'attributes' => $productData['attributes'] ?? $this->getLocalAttributes(),
            'variables' => [],
            'categories' => $productData['categories'] ?? $this->getLocalCategories(),
            'productClassification' => $productData['productClassification'] ?? $this->getLocalProductClassification(),
            'productData' => $this->productData,
        ];
        $this->appliesTo = $data['appliesTo'];
        $this->scriptType = $data['scriptType'];
        $this->userId = $data['userId'];
        $this->isAnyCategory = (isset($data['categories']) && count($data['categories']) > 0);
        $this->isSkuProduct = false;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Taking a clone of running job that extends this class
        $jobFailService = JobFailService::factory();
        $jobFailService->job = $this->job;

        $scriptRunner = new ExecuteScriptService;
        $responseData = $scriptRunner->validateScript($this->request);

        if ($this->scriptType === 'run_script') {
            $this->saveData($responseData);
        }

        $this->dataTransformation->increment('success_count');
    }

    public function failed($exception): void
    {
        echo PHP_EOL, PHP_EOL, 'ExecuteScriptJob failed :: '.$exception->getMessage(), PHP_EOL, PHP_EOL;
        Log::info('ExecuteScriptJob failed', [
            'exception' => $exception,
        ]);

        $this->dataTransformation->increment('failed_count');
        $jobFailService = JobFailService::factory();
        $job = $jobFailService->job;

        DataTransformationError::create([
            'data_transformation_id' => $this->dataTransformation->id,
            'data_transformation' => json_encode($this->productData, JSON_THROW_ON_ERROR),
            'job_name' => get_class($this),
            'uuid' => $job->uuid(),
            'error_message' => $exception->getMessage(),
        ]);
    }

    private function getProductFields(): array
    {
        $fields = $this->getProductImageRecords();
        $productData = $this->getProductDataFromDatabase();
        if (! empty($productData)) {
            $fields = array_merge($fields, $productData);
        }

        return $fields;
    }

    private function saveData($data): void
    {
        if ($this->appliesTo === 'Products') {
            $this->saveProductData($data);
        }

        if ($this->appliesTo === 'Categories') {
            $this->saveCategoryData($data);
        }
    }

    private function saveProductData($data): void
    {
        /* Prepare data */
        $productData = $this->prepareData($data['fields']);
        $this->isSkuProduct = (count(json_decode($productData['sku_list'], false, 512, JSON_THROW_ON_ERROR)) === 1)
                              && isset($productData['sku_productcode'])
                              && ($productData['sku_productcode']
                                  === json_decode($productData['sku_list'], false, 512, JSON_THROW_ON_ERROR)[0][1]);

        $productCode = $this->isSkuProduct
            ? $productData['sku_productcode']
            : $productData['product_code'];

        $productData['product_name'] = $this->isSkuProduct
            ? $productData['sku_name']
            : $productData['product_name'];

        if ($this->isSkuProduct) {
            unset($productData['product_code']);
        }

        /* Save product to database */
        $product = Product::query()->updateOrCreate(
            [
                'product_code' => $productCode,
            ],
            $productData
        );

        /* Save product images to database */
        if ((isset($productData['main']) && ! empty($productData['main']))
            || (isset($productData['thumbnail']) && ! empty($productData['thumbnail']))
            || (isset($productData['additional']) && count($productData['additional']) > 0)) {
            $this->saveProductImages($productData, $product->id);
        }

        /* Save categories to product */
        if (count($data['categories']) > 0) {
            $this->saveCategories($data['categories'], $product->id);
        } elseif ($this->isAnyCategory) {
            CategoryProduct::query()
                ->where('product_id', $product->id)
                ->delete();
        }

        /* Save productClassification to product */
        if (! empty($data['productClassification'])) {
            $this->saveProductClassification($data['productClassification'], $product->id);
        }

        /* Save product attributes to database */
        if (count($data['attributes']) > 0) {
            $this->saveAttributesToDatabase($data['attributes'], $product->id);
        }
    }

    private function prepareData(array $data): array
    {
        /* Prepare product data */
        $productData = [];
        collect(array_map('unserialize', array_unique(array_map('serialize', $data))))
            ->map(function ($item) use (&$productData) {
                if ($item['name'] === 'msrp' && strpos($item['value'], '$') !== false) {
                    $item['value'] = (float) str_replace('$', '', $item['value']);
                }

                $productData[$item['name']] = $item['value'];
            });
        $productData['user_id'] = $this->userId;

        return $productData;
    }

    private function saveAttributesToDatabase(array $attributes, int $productId): void
    {
        collect($attributes)->map(function ($item) use ($productId) {
            $matchedAttributes = Attribute::query()
                ->where('slug', 'like', '%'.$item['name'].'%')
                ->get();

            if (count($matchedAttributes) > 0) {
                if (count($matchedAttributes) === 1) {
                    $attributeData = $matchedAttributes->toArray()[0];
                } else {
                    $attributeData = collect($matchedAttributes->toArray())
                        ->where('slug', $item['name'])
                        ->first();
                }

                if (! $item['allow_multiple']) {
                    AttributeProduct::query()->updateORCreate(
                        [
                            'product_id' => $productId,
                            'attribute_id' => $attributeData['id'],
                        ],
                        [
                            'attribute_value' => $item['value'],
                        ]
                    );
                } else {
                    foreach ($item['value'] as $value) {
                        AttributeProduct::create(
                            [
                                'product_id' => $productId,
                                'attribute_id' => $attributeData['id'],
                                'attribute_value' => $value,
                            ]
                        );
                    }
                }
            } else {
                $attribute = Attribute::create([
                    'name' => $item['name'],
                    'slug' => $item['name'],
                    'type' => 'text',
                    'is_new' => 1,
                ]);

                AttributeProduct::create([
                    'product_id' => $productId,
                    'attribute_id' => $attribute->id,
                    'attribute_value' => $item['value'],
                ]);
            }
        });
    }

    private function saveProductImages(array $productData, $id): void
    {
        $productImages = [];
        if (isset($productData['main']) && ! empty($productData['main'])) {
            $productImages['main'] = $productData['main'];
        }
        if (isset($productData['thumbnail']) && ! empty($productData['thumbnail'])) {
            $productImages['thumbnail'] = $productData['thumbnail'];
        }
        if (isset($productData['additional']) && count($productData['additional']) > 0) {
            $productImages['additional'] = $productData['additional'];
        }

        ProductImage::query()->updateOrCreate(
            [
                'product_id' => $id,
            ],
            $productImages
        );
    }

    private function saveCategories($categories, $id): void
    {
        $allCategories = [];

        foreach ($categories as $category) {
            /* Get all matched category by name */
            $matchedCategories = Category::query()
                ->where('category_name', 'like', '%'.$category.'%')
                ->get();
            /* Get specific category by local name from matched categories */
            $isCategoryExist = collect($matchedCategories->toArray())->where('label', $category)->first();

            /* Handling If category not exist */
            if (empty($isCategoryExist)) {
                Log::info('==========================================================');
                Log::error('Category not found :: '.$category);
                Log::info('==========================================================');

                continue;
            }

            /* Update or create category */
            CategoryProduct::query()->updateOrCreate([
                'product_id' => $id,
                'category_id' => $isCategoryExist['id'],
            ]);

            $allCategories[] = $isCategoryExist['id'];
        }

        /* Remove older categories from product */
        CategoryProduct::query()
            ->where('product_id', $id)
            ->whereNotIn('category_id', $allCategories)
            ->delete();
    }

    private function saveProductClassification($productClassification, $id): void
    {
        /* Getting product classification by title */
        $isProductClassificationExist = ProductClassification::query()
            ->where('title', 'like', '%'.$productClassification.'%')
            ->first();

        /* Handling If product classification not found */
        if (empty($isProductClassificationExist)) {
            Log::info('==========================================================');
            Log::error('Product Classification not found :: '.$productClassification);
            Log::info('==========================================================');

            return;
        }

        /* Updating product classification */
        Product::query()->where('id', $id)->update([
            'product_classification_id' => $isProductClassificationExist->id,
        ]);
    }

    private function saveCategoryData($data): void
    {
        // TODO: Implement saveCategoryData() method.
    }

    /**
     * Get attributes by product_id from local database
     */
    private function getLocalAttributes(): array
    {
        $formattedAttributes = [];
        $attributes = AttributeProduct::query()
            ->where('product_id', $this->productData['Product_Id'])
            ->get();

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                $formattedAttributes[] = [
                    'name' => Attribute::where('id', $attribute->attribute_id)->first()->local_name,
                    'value' => $attribute->attribute_value,
                    'allow_multiple' => false,
                ];
            }
        }

        return $formattedAttributes;
    }

    /**
     * Get categories by product_id from local database
     */
    private function getLocalCategories(): array
    {
        $categories = [];
        $productCategories = CategoryProduct::query()
            ->where('product_id', $this->productData['Product_Id'])
            ->get();

        if (count($productCategories) > 0) {
            foreach ($productCategories as $productCategory) {
                $categories[] = Category::whereId($productCategory->category_id)->first()->label;
            }
        }

        return $categories;
    }

    /**
     * Get product classification by product_id from local database
     */
    private function getLocalProductClassification(): ?string
    {
        $productClassification = null;
        $productId =
            optional(Product::whereId($this->productData['Product_Id'])->first())->product_classification_id;

        if (! empty($productId)) {
            $productClassification = ProductClassification::query()->whereId($productId)->first()->label;
        }

        return $productClassification;
    }

    private function getProductImageRecords(): array
    {
        $fields = [];
        if (array_key_exists('Product_Id', $this->productData)) {
            $productImages = ProductImage::query()
                ->select('main', 'thumbnail', 'additional')
                ->where('product_id', $this->productData['Product_Id'])
                ->first();
            if (! empty($productImages)) {
                $images = $productImages->toArray();
                foreach ($images as $key => $value) {
                    $fields[] = [
                        'name' => strtolower($key),
                        'value' => $value,
                    ];
                }
            }
        }

        return $fields;
    }

    private function getProductDataFromDatabase(): array
    {
        $fields = [];
        if (array_key_exists('Product_Id', $this->productData)) {
            $productData = Product::query()
                ->select('product_code',
                    'product_name',
                    'product_slug',
                    'product_type',
                    'products_list',
                    'has_sku',
                    'description',
                    'ean_number',
                    'gtin_number',
                    'upc_number',
                    'asin',
                    'is_new',
                    'is_updated',
                    'manufacturer',
                    'model_code',
                    'model_name',
                    'msrp',
                    'parent_id',
                    'sku_id',
                    'sku_default_attributes',
                    'sku_part_number',
                    'user_id',
                    'product_classification_id',
                    'status',
                    'selling_price')
                ->find($this->productData['Product_Id']);
            if (! empty($productData)) {
                $data = array_merge($this->productData, $productData->toArray());
            } else {
                $data = $this->productData;
            }
            foreach ($data as $key => $value) {
                $checkIndexExists = collect($fields)->where('name', strtolower($key))->first();
                if (empty($checkIndexExists)) {
                    $fields[] = [
                        'name' => strtolower($key),
                        'value' => $value,
                    ];
                }
            }
        }

        return $fields;
    }
}
