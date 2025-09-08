<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\AttributeProduct;
use Amplify\System\Backend\Models\Product;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class AttributeProductServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attributeProduct;

    public $attribute_id;

    public $product_id;

    public $multipleValuesDataOfAttribute = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->className = get_class($this);

        parent::__construct($data);
    }

    /**
     * @throws ErrorException
     */
    protected function getMappingProcessed($aCsv): void
    {
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $attributeProduct = ! empty($this->attribute_id) ? AttributeProduct::where([
            'product_id' => $this->product_id,
            'attribute_id' => $this->attribute_id,
        ])->first() : null;

        empty($attributeProduct)
            ? $this->handleCreateOperation($aCsv)
            : $this->handleUpdateOperation($aCsv, $attributeProduct);

        App::setLocale($this->default_locale);
    }

    /**
     * @throws ErrorException
     */
    protected function handleCreateOperation($aCsv): void
    {
        $this->attributeProduct = new AttributeProduct;
        $this->saveDataToDatabase($aCsv);
    }

    /**
     * @throws ErrorException
     */
    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->attributeProduct = $entity;
        $this->is_updating = true;
        $this->saveDataToDatabase($aCsv);
        $this->is_updating = false;
    }

    protected function saveMultipleValuesForAttribute(): void
    {
        foreach ($this->multipleValuesDataOfAttribute['attributeValues'] as $attributeValue) {
            AttributeProduct::create([
                'product_id' => $this->product_id,
                'attribute_id' => $this->attribute_id,
                'attribute_value' => $attributeValue,
            ]);
        }
        $this->multipleValuesDataOfAttribute = [];
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->map_to !== 'Ignore'
                    && $item->field_or_attribute_name === 'Attribute'
                    && $item->field_name === 'name') {
                    $this->attribute_id = $this->getAttributeIdByName($aCsv[$index]);
                }

                if ($item->map_to !== 'Ignore'
                    && $item->field_or_attribute_name === 'product_id') {
                    $this->product_id = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore'
                    && $item->field_or_attribute_name === 'attribute_id') {
                    $this->attribute_id = $aCsv[$index];
                }
            });
    }

    /**
     * @throws ErrorException
     */
    protected function saveDataToDatabase($aCsv): void
    {
        $this->handleColumnMapping();

        // Save attributeProduct
        if (isset($this->product_id) && ! empty($this->product_id)) {
            if (Product::where('id', $this->product_id)->exists()) {
                if (count($this->multipleValuesDataOfAttribute) > 0) {
                    if ($this->is_updating) {
                        AttributeProduct::where([
                            'product_id' => $this->product_id,
                            'attribute_id' => $this->attribute_id,
                        ])->delete();
                    }

                    $this->saveMultipleValuesForAttribute();
                } else {
                    $this->attributeProduct->save();
                }
            } else {
                throw new ErrorException('Product not found');
            }
        }
    }

    protected function mapToField($item, $value): void
    {
        $separator = $item->separator;

        if (! empty($value)) {
            if (! $this->attributeProduct instanceof AttributeProduct) {
                $this->attributeProduct = new AttributeProduct;
            }
            if ($item->field_or_attribute_name === 'attribute_value') {
                if (! empty($separator) && str_contains($value, $separator)) {
                    $this->multipleValuesDataOfAttribute['attributeValues'] = explode($separator, $value);
                } else {
                    $this->attributeProduct->attribute_id = $this->attribute_id;
                    $this->attributeProduct->attribute_value = $value;
                }
            } else {
                $this->attributeProduct->{$item->field_or_attribute_name} = $value;
            }
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        $modelName = $item->field_or_attribute_name;
        $field_name = $item->field_name;
        switch ($modelName) {
            case 'Attribute':
                if ($field_name === 'name') {
                    $matchedAttributes = Attribute::query()
                        ->where('name', 'like', '%'.$value.'%')
                        ->get();

                    /* If attribute exist in database then return the id */
                    if (count($matchedAttributes) > 0) {
                        $attributeData = collect($matchedAttributes->toArray())
                            ->where('local_name', $value)
                            ->first();
                        $this->attribute_id = $attributeData['id'];
                        break;
                    }

                    /* If attribute doesn't exist in database then create a new attribute and return the id */
                    $attribute = Attribute::create([
                        'name' => $value,
                        'slug' => $value,
                        'type' => 'text',
                        'is_new' => 1,
                    ]);

                    $this->attribute_id = $attribute->id;
                }
                break;
            default:
                break;
        }
    }

    private function getAttributeIdByName($attributeName)
    {
        $matchedAttributes = Attribute::query()
            ->where('name', 'like', '%'.$attributeName.'%')
            ->get();

        /* If attribute exist in database then return the id */
        if (count($matchedAttributes) > 0) {
            $attributeData = $matchedAttributes->where('local_name', $attributeName)->first();

            return $attributeData['id'];
        }

        /* If attribute doesn't exist in database then create a new attribute and return the id */
        $attribute = Attribute::create([
            'name' => $attributeName,
            'type' => 'text',
            'is_new' => 1,
        ]);

        return $attribute->id;
    }
}
