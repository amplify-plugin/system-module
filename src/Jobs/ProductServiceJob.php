<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\AttributeProduct;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\CategoryProduct;
use Amplify\System\Backend\Models\ModelCode;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductClassification;
use Amplify\System\Backend\Models\ProductImage;
use Amplify\System\Backend\Models\SkuProduct;
use Amplify\System\Utility\Models\ImportDefinitionJobProduct;
use Amplify\System\Utility\Services\DataTransformation\ExecuteScriptService;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $product_id
 */
class ProductServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $product;

    public $productImage;

    public $attributeProduct;

    public $categoryProduct;

    public $imageSeparator;

    public $product_code;

    public $product_id;

    public $parent_id;

    public $category_id;

    public $multipleValuesDataOfAttribute = [];

    public $attributesFromImport = [];

    public $productImageFields = ['additional', 'main', 'thumbnail'];

    public $hasAnyProductImage = false;

    public $hasAnyAttribute = false;

    public $hasImportedAttribute = false;

    public $modelCodes = [];

    public $hasAnyModelCode = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->imageSeparator = $data['imageSeparator'];
        $this->className = get_class($this);

        parent::__construct($data);
    }

    protected function getMappingProcessed($aCsv): void
    {
        Schema::disableForeignKeyConstraints();
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $product = match (true) {
            ! empty($this->product_id) => Product::find($this->product_id),
            ! empty($this->product_code) => Product::where('product_code', $this->product_code)->first(),
            default => null
        };

        empty($product) ? $this->handleCreateOperation($aCsv) : $this->handleUpdateOperation($aCsv, $product);

        App::setLocale($this->default_locale);
        Schema::enableForeignKeyConstraints();
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->map_to !== 'Ignore' && $item->field_or_attribute_name === 'id') {
                    $this->product_id = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore' && $item->field_or_attribute_name === 'parent_id') {
                    $this->parent_id = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore' && $item->field_or_attribute_name === 'product_code') {
                    $this->product_code = trim($aCsv[$index]);
                }

                if ($item->map_to !== 'Ignore' && $item->field_name === 'category_id') {
                    $this->category_id = $aCsv[$index];
                }

                if (! $this->hasAnyProductImage && $item->map_to !== 'Ignore' && in_array($item->field_name, $this->productImageFields, true)) {
                    $this->hasAnyProductImage = true;
                    $productImage = ProductImage::query()->where('product_id', $this->product_id)->first();
                    $this->productImage = ! empty($productImage) ? $productImage : new ProductImage;
                }

                if (! $this->hasAnyAttribute && $item->map_to === 'Attribute') {
                    $this->hasAnyAttribute = true;
                }

                if (! $this->hasAnyModelCode && $item->field_or_attribute_name === 'Model Code') {
                    $this->hasAnyModelCode = true;
                }
            });

//        if ($this->product_id && $this->product_code) {
//            $pExtsts = Product::where('product_code', $this->product_code)->where('id', '!=', $this->product_id)->exists();
//
//            if ($pExtsts) {
//                throw new ErrorException('Product code can not be duplicated.');
//            }
//        }
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->product = new Product;

        if ($this->hasAnyProductImage) {
            $this->productImage = new ProductImage;
        }

        if ($this->hasAnyAttribute) {
            $this->attributeProduct = new AttributeProduct;
        }

        if (isset($this->category_id)) {
            $this->categoryProduct = new CategoryProduct;
        }

        $this->saveDataToDatabase($aCsv);
    }

    protected function saveDataToDatabase($aCsv): void
    {
        $this->handleColumnMapping();

        /* Saving product */
        if (! $this->is_updating) {
            $this->product->is_new = 1;
            $this->product->user_id = $this->userId;
        }

        $this->product->is_updated = (bool) $this->is_updating;

        /* Checking if category and data transformation exists */
        $importType = $this->is_updating ? 'import - update' : 'import - create';

        if (! empty($this->category_id) && $this->getDataTransformationsForImport('boolean', $importType)) {
            /* Make data transformation */
            $transformedData = $this->runDataTransformation(
                $this->product->toArray(),
                $this->attributeProduct->toArray(),
                $this->category_id ?? null,
                $importType
            );

            /* Merging transformed data with product data */
            $this->mergeTransformedDataWithProductModel($transformedData['fields']);
        }

        if ($this->product->save()) {
            if ($this->parent_id) {
                // $this->product->parentProducts()->sync($this->parent_id);
                if (! SkuProduct::where(['parent_id' => $this->parent_id, 'sku_id' => $this->product->id])->exists()) {
                    SkuProduct::insert([
                        'parent_id' => $this->parent_id,
                        'sku_id' => $this->product->id,
                    ]);
                }
                Product::where('id', $this->parent_id)->update(['has_sku' => true]);
            }

            /* Saving data to pivot table - ImportDefinitionJobProduct */

            $this->product->importDefinitionJobProduct()->save(
                new ImportDefinitionJobProduct([
                    'import_definition_job_id' => $this->importJobId,
                ])
            );

            /*        ImportDefinitionJobProduct::query()->create([
                        'import_definition_job_id' => $this->importJobId,
                        'product_id' => $this->product_id,
                    ]);*/

            /* Saving productImage */
            if ($this->hasAnyProductImage) {
                $this->product->productImage()->save(
                    $this->productImage
                );
                /*            $this->productImage->product_id = $this->product_id;
                            $this->productImage->save();*/
            }

            /* Saving Attribute */
            if ($this->hasAnyAttribute) {
                if (count($this->multipleValuesDataOfAttribute) > 0) {
                    $this->saveMultipleValuesForAttribute();
                } else {
                    if ($this->attributeProduct->product_id) {
                        $this->attributeProduct->save();
                    }
                }
            }

            if ($this->hasImportedAttribute) {
                $this->saveImportedAttribute();
            }

            if ($this->hasAnyModelCode) {
                $modelCodes = $this->modelCodes;

                $modelCodesIds = [];
                foreach ($modelCodes['code'] as $key => $modelCode) {
                    $modelCodesIds[] = ModelCode::updateOrCreate(
                        ['code' => $modelCode],
                        ['name' => $modelCodes['name'][$key] ?? null]
                    )->id;
                }

                $this->product->modelCodes()->syncWithoutDetaching($modelCodesIds);
            }

            /* Saving categoryProduct */
            if (isset($this->category_id) && ! empty($this->category_id)) {
                $this->product->categoryProduct()->save($this->categoryProduct);
                /*            $this->categoryProduct->product_id = $this->product_id;
                            $this->categoryProduct->save();*/
            }
        }
    }

    /**
     * @return array|bool
     */
    private function getDataTransformationsForImport(string $returnType, string $importType)
    {
        if (getDataTransformations('Products', [$this->category_id], 'save', 'boolean')
            || getDataTransformations('Products', [$this->category_id], $importType, 'boolean')
        ) {
            if ($returnType === 'boolean') {
                return true;
            }

            return array_merge(
                getDataTransformations('Products', [$this->category_id], 'save', $returnType)->toArray(),
                getDataTransformations('Products', [$this->category_id], $importType, $returnType)->toArray(),
            );
        }

        if ($returnType === 'boolean') {
            return false;
        }

        return [];
    }

    private function runDataTransformation($productData, $attributeProduct, $categoryId, $importType): array
    {
        /* Prepare product fields */
        $preparedData = $this->prepareData($productData, $attributeProduct, $categoryId);

        /* Making data transformation */
        $dataTransformationData = $this->makeDataTransformation($preparedData, $importType);

        /* Making $dataTransformation data compatible with model data pattern */
        return $this->makeTransformedDataCompatibleWithModelData($dataTransformationData);
    }

    private function prepareData($productData, $attributeProduct, $categoryId): array
    {
        $fields = [];

        $productData['product_name'] = $productData['local_product_name'];
        $productData['model_name'] = $productData['local_model_name'];
        $productData['description'] = $productData['local_description'];

        unset(
            $productData['old_image'],
            $productData['local_product_name'],
            $productData['local_model_name'],
            $productData['local_description'],
        );

        foreach ($productData as $key => $value) {
            $fields[] = [
                'name' => $key,
                'value' => $value,
            ];
        }

        /* Prepare $productClassification */
        $productClassification = ! empty($productData['product_classification_id'])
            ? collect(ProductClassification::query()
                ->where('id', $productData['product_classification_id'])
                ->first()
                ->toArray())['label']
            : null;

        /* Prepare $productCategory */
        $productCategory = ! empty($categoryId)
            ? [collect(Category::query()
                ->where('id', $categoryId)
                ->first()
                ->toArray())['label']]
            : null;

        /* Prepare product attribute as name and value pair */
        $productAttributes = [];
        if ($this->hasAnyAttribute) {
            $productAttributes[] = [
                'name' => Attribute::query()->find($attributeProduct['attribute_id'])->name,
                'value' => $attributeProduct['attribute_value'][$this->locale],
                'allow_multiple' => false,
            ];
        }

        return [
            'script' => [],
            'fields' => $fields,
            'attributes' => $productAttributes,
            'variables' => [],
            'categories' => $productCategory,
            'productClassification' => $productClassification,
        ];
    }

    private function makeDataTransformation(array $preparedData, $importType): array
    {
        collect(
            $this->getDataTransformationsForImport('array', $importType)
        )->map(function ($item) use (&$preparedData) {
            /* Preparing script */
            $preparedData['script'] = preg_split("/\r\n|\n|\r/", $item['scripts']);
            /* Make data-transformation using 'ExecuteScriptService' */
            $executeScriptService = new ExecuteScriptService;
            $responseData = $executeScriptService->validateScript($preparedData);
            /* Updating $preparedData for next transformation */
            $preparedData = [
                'fields' => $responseData['fields'],
                'attributes' => $responseData['attributes'],
                'variables' => $responseData['variables'],
                'categories' => $responseData['categories'],
                'productClassification' => $responseData['productClassification'],
            ];
        });

        return $preparedData;
    }

    private function makeTransformedDataCompatibleWithModelData(array $transformedData): array
    {
        /* Making $transformedData['fields'] compatible with $requestData format */
        $productFields = [];
        collect($transformedData['fields'])->map(function ($item) use (&$productFields) {
            if ($item['name'] === 'product_name') {
                $productFields['product_name'][$this->locale] = $item['value'];
            } elseif ($item['name'] === 'model_name') {
                $productFields['model_name'][$this->locale] = $item['value'];
            } elseif ($item['name'] === 'description') {
                $productFields['description'][$this->locale] = $item['value'];
            } else {
                $productFields[$item['name']] = $item['value'];
            }
        });

        /* Making $transformedData['categories'] compatible with $requestData format */
        $categoryIds = [];
        collect($transformedData['categories'])->map(function ($item) use (&$categoryIds) {
            $matchedCategories = Category::query()
                ->where('category_name', 'like', '%'.$item.'%')
                ->get();
            if (count($matchedCategories) > 0) {
                $category = collect($matchedCategories->toArray())->where('label', $item)->first();
                $categoryIds[] = $category['id'];
            }
        });
        /* Set category_id */
        if (count($categoryIds) > 0) {
            $this->category_id = $categoryIds[0];
        }

        /* Making $transformedData['productClassification'] compatible with $requestData format */
        $productClassificationId = null;
        $matchedProductClassification = ProductClassification::query()
            ->where('title', 'like', '%'.$transformedData['productClassification'].'%')
            ->get();
        if (count($matchedProductClassification) > 0) {
            $productClassificationData = collect($matchedProductClassification->toArray())->first();
            $productClassificationId = $productClassificationData['id'];
            $productFields['product_classification_id'] = $productClassificationId;
        }

        /* Set $transformedData['attributes'] as importJob */
        if (count($transformedData['attributes']) > 0) {
            collect($transformedData['attributes'])->map(function ($item) {
                $matchedAttributes = Attribute::query()
                    ->where('name', 'like', '%'.$item['name'].'%')
                    ->get();

                if (count($matchedAttributes) > 0) {
                    $attributeData = collect($matchedAttributes->toArray())
                        ->where('local_name', $item['name'])
                        ->first();

                    if (! empty($attributeData)) {
                        $this->attributesFromImport[] = [
                            'attribute_id' => $attributeData['id'],
                            'attribute_value' => $item['value'],
                        ];
                    }
                }
            });

            if (count($this->attributesFromImport) > 0) {
                $this->hasImportedAttribute = true;
            }
        }

        return [
            'fields' => $productFields,
            'categories' => $categoryIds,
            'productClassification' => $productClassificationId,
        ];
    }

    private function mergeTransformedDataWithProductModel(array $transformedData): void
    {
        $this->product = $this->product->setHidden([
            'old_image', 'local_product_name', 'local_model_name', 'local_description',
        ]);

        foreach ($transformedData as $fieldName => $fieldValue) {
            $this->product->{$fieldName} = $fieldValue;
        }

        unset(
            $this->product->old_image,
            $this->product->local_product_name,
            $this->product->local_model_name,
            $this->product->local_description,
        );
    }

    protected function saveMultipleValuesForAttribute(): void
    {
        foreach ($this->multipleValuesDataOfAttribute['attributeValues'] as $attributeValue) {
            AttributeProduct::create([
                'locale' => $this->locale,
                'product_id' => $this->product_id,
                'attribute_id' => $this->multipleValuesDataOfAttribute['attribute_id'],
                'attribute_value' => $attributeValue,
            ]);
        }
        $this->multipleValuesDataOfAttribute = [];
    }

    private function saveImportedAttribute(): void
    {
        foreach ($this->attributesFromImport as $attribute) {
            AttributeProduct::query()->updateORCreate(
                [
                    'product_id' => $this->product_id,
                    'attribute_id' => $attribute['attribute_id'],
                ],
                [
                    'attribute_value' => $attribute['attribute_value'],
                ]
            );
        }

        $this->hasImportedAttribute = false;
        $this->attributesFromImport = [];
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->product = $entity;
        $this->is_updating = true;
        $this->generateInstanceOfRelatedTable();
        $this->saveDataToDatabase($aCsv);
        $this->is_updating = false;
    }

    protected function generateInstanceOfRelatedTable(): void
    {
        // Generating productImage instance
        $productImage = ProductImage::query()->where(['product_id' => $this->product_id])->first();

        $this->productImage = ! empty($productImage) ? $productImage : new ProductImage;
    }

    /**
     * @throws ErrorException
     */
    protected function mapToField($item, $value): void
    {
        if (($item->field_or_attribute_name === 'upc_number') && ! empty(trim($value)) && strlen(trim($value)) !== 12) {
            throw new ErrorException('UPC Number must be 12 characters');
        }

        if (! in_array($this->product->product_type, Product::PRODUCT_TYPES, true)) {
            $this->product->product_type = 'normal';
        }

        if ((! empty($value) && $this->is_updating) || ! $this->is_updating) {
            $this->product->{$item->field_or_attribute_name} = $value;
        }
    }

    /**
     * @throws ErrorException+
     */
    protected function mapToAttribute($item, $value): void
    {
        if ($this->hasAnyAttribute) {
            $fieldName = $item->field_or_attribute_name;
            $fieldValue = $value;
            $action = $item->attribute_value;
            $separator = $item->separator;
            $productId = $this->product_id;

            $isAttributeExist = Attribute::query()
                ->where('name', 'LIKE', "%\\\"$fieldName\\\"%")
                ->first();

            if (! empty($isAttributeExist)) {
                $attributeId = $isAttributeExist->id;

                if ($this->is_updating) {
                    $attributeProducts = AttributeProduct::query()->where([
                        'product_id' => $productId,
                        'attribute_id' => $attributeId,
                    ])->get();

                    if (count($attributeProducts) > 0) {
                        AttributeProduct::query()->where([
                            'product_id' => $productId,
                            'attribute_id' => $attributeId,
                        ])->delete();
                    }
                }
                $this->attributeProduct = new AttributeProduct;

                if (str_contains($fieldValue, $separator)) {
                    if ($action === 'Add') {
                        $this->multipleValuesDataOfAttribute['attributeValues'] = explode($separator, $fieldValue);
                        $this->multipleValuesDataOfAttribute['attribute_id'] = $attributeId;
                    }
                } else {
                    if ($action === 'Add') {
                        $this->attributeProduct->locale = $this->locale;
                        $this->attributeProduct->product_id = $productId;
                        $this->attributeProduct->attribute_id = $attributeId;
                        $this->attributeProduct->attribute_value = $fieldValue;
                    }
                }
            } else {
                $msg = 'Attribute ( '.$fieldName.' ) not found';
                throw new ErrorException($msg);
            }
        }
    }

    /**
     * @throws ErrorException
     */
    protected function mapToTable($item, $value): void
    {
        $modelName = $item->field_or_attribute_name;
        $field_name = $item->field_name;
        $separator = $item->separator;
        switch ($modelName) {
            case 'Product Image':
                if ($field_name === 'additional' && $separator !== '') {
                    $value = explode(',', str_replace($separator, $this->imageSeparator, $value));
                }

                if ((! empty($value) && $this->is_updating) || ! $this->is_updating) {
                    $this->productImage->{$field_name} = $value;
                }

                break;

            case 'Model Code':
                if ($separator !== '') {
                    $value = explode($separator, $value);
                }

                if ((! empty($value) && $this->is_updating) || ! $this->is_updating) {
                    $this->modelCodes[$field_name] = $value;
                }
                break;

            case 'Category Product':
                if (! empty($value)) {
                    $is_category_exist = Category::query()->find((int) $value);
                    if (empty($is_category_exist)) {
                        throw new ErrorException('Category not found');
                    }

                    if ($this->is_updating) {
                        $categoryProduct = CategoryProduct::query()->where('product_id', $this->product_id)->get();
                        if (count($categoryProduct) > 0) {
                            CategoryProduct::query()->where('product_id', $this->product_id)->delete();
                        }
                    }
                    $this->categoryProduct = new CategoryProduct;
                    $this->categoryProduct->{$field_name} = $value;
                }
                break;
            default:
                break;
        }
    }
}
