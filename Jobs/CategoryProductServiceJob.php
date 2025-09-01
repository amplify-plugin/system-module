<?php

namespace Amplify\System\Jobs;

use App\Models\CategoryProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

/**
 * @property int $category_product_id
 */
class CategoryProductServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $categoryProduct;

    public $category_product_id;

    public $category_id;

    public $product_id;

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

    protected function getMappingProcessed($aCsv): void
    {
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $categoryProduct = CategoryProduct::query()->where([['product_id', '=', $this->product_id], ['category_id', '=', $this->category_id]])->first();
        empty($categoryProduct)
            ? $this->handleCreateOperation($aCsv)
            : $this->handleUpdateOperation($aCsv, $categoryProduct);

        App::setLocale($this->default_locale);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->field_or_attribute_name === 'id') {
                    $this->category_product_id = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore' && $item->field_or_attribute_name === 'category_id') {
                    $this->category_id = $aCsv[$index];
                }

                if ($item->map_to !== 'Ignore' && $item->field_or_attribute_name === 'product_id') {
                    $this->product_id = $aCsv[$index];
                }
            });
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->categoryProduct = new CategoryProduct;
        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->categoryProduct = $entity;
        $this->is_updating = true;
        $this->saveDataToDatabase($aCsv);
        $this->is_updating = false;
    }

    protected function saveDataToDatabase($aCsv): void
    {
        $this->handleColumnMapping();
        $this->categoryProduct->save();
    }

    protected function mapToField($item, $value): void
    {
        if ((! empty($value) && $this->is_updating) || ! $this->is_updating) {
            $this->categoryProduct->{$item->field_or_attribute_name} = $value;
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        // TODO: Implement mapToAttribute() method.
    }

    protected function mapToTable($item, $value): void
    {
        // TODO: Implement mapToTable() method.
    }
}
