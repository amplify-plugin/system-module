<?php

namespace Amplify\System\Jobs;

use App\Models\Attribute;
use App\Models\AttributeProduct;
use App\Models\Product;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class AttributeServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attribute;

    public $attributeProduct;

    public $attribute_id;

    public $product_id;

    public $attribute_name;

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
        $attribute = null;
        if (! empty($this->attribute_id)) {
            $attribute = Attribute::query()->with('products')->find($this->attribute_id);
        } elseif (! empty($this->attribute_name)) {
            $attribute = Attribute::query()->where('name', 'like', "%{$this->attribute_name}%")->with('products')->first();
        }

        empty($attribute)
            ? $this->handleCreateOperation($aCsv)
            : $this->handleUpdateOperation($aCsv, $attribute);

        App::setLocale($this->default_locale);
    }

    /**
     * @throws ErrorException
     */
    protected function handleCreateOperation($aCsv): void
    {
        $this->attribute = new Attribute;
        if (isset($this->product_id)) {
            $this->attributeProduct = new AttributeProduct;
        }
        $this->saveDataToDatabase($aCsv);
    }

    /**
     * @throws ErrorException
     */
    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->attribute = $entity;
        $this->is_updating = true;

        if (isset($this->product_id)) {
            $attributeProduct = AttributeProduct::where([
                'product_id' => $this->product_id,
                'attribute_id' => $this->attribute_id ?? $entity->id,
            ])->first();

            if (! empty($attributeProduct)) {
                $this->attributeProduct = $attributeProduct;
            } else {
                $this->attributeProduct = new AttributeProduct;
            }
        }

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
                if ($item->field_or_attribute_name === 'id') {
                    $this->attribute_id = (int) $aCsv[$index];
                }

                if ($item->field_or_attribute_name === 'name') {
                    $this->attribute_name = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore' && $item->field_name === 'product_id') {
                    $this->product_id = (int) $aCsv[$index];
                }
            });
    }

    /**
     * @throws ErrorException
     */
    protected function saveDataToDatabase($aCsv): void
    {
        $this->handleColumnMapping();

        // Save attribute
        if (! $this->is_updating) {
            $this->attribute->is_new = 1;
        }

        $this->attribute->slug = $this->attribute->name;
        $this->attribute->name = $this->attribute->display_name ?? $this->attribute->name;
        unset(
            $this->attribute->display_name,
        );

        if (! empty($this->attribute->name)) {
            $this->attribute->is_updated = (bool) $this->is_updating;
            $this->attribute->save();
        }

        // Save attributeProduct
        if (isset($this->product_id) && ! empty($this->product_id)) {
            if (! empty(Product::query()->find($this->product_id))) {
                if (count($this->multipleValuesDataOfAttribute) > 0) {
                    if ($this->is_updating) {
                        AttributeProduct::query()->where([
                            'product_id' => $this->product_id,
                            'attribute_id' => $this->attribute_id ?? $this->attribute->id,
                        ])->delete();
                    }

                    $this->saveMultipleValuesForAttribute();
                } else {
                    $this->attributeProduct->attribute_id = $this->attribute_id ?? $this->attribute->id;
                    $this->attributeProduct->save();
                }
            } else {
                throw new ErrorException('Product not found');
            }
        }
    }

    /**
     * @throws ErrorException
     */
    protected function mapToField($item, $value): void
    {
        if ($this->is_updating) {
            if (! empty($value)) {
                $this->attribute->{$item->field_or_attribute_name} = $value;
            }
        } else {
            if ($item->field_or_attribute_name === 'name') {
                $is_attribute_name_exist = Attribute::query()->where('name', 'LIKE', "%{$value}%")->get();
                if (count($is_attribute_name_exist) > 0) {
                    throw new ErrorException('Attribute name already exist');
                }
            }

            $this->attribute->{$item->field_or_attribute_name} = $value;
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        $separator = $item->separator;

        if (! empty($value)) {
            if ($item->field_name === 'attribute_value') {
                if (! empty($separator) && str_contains($value, $separator)) {
                    $this->multipleValuesDataOfAttribute['attributeValues'] = explode($separator, $value);
                } else {
                    $this->attributeProduct->attribute_id = $this->attribute_id ?? 0;
                    $this->attributeProduct->attribute_value = $value;
                }
            } else {
                $this->attributeProduct->{$item->field_name} = $value;
            }
        }
    }
}
