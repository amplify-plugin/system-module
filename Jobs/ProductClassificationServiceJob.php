<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\ProductClassification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ProductClassificationServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ProductClassification $product_classification;

    public ?int $product_classification_id = null;

    /**
     * Create a new job instance.
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

        $product_classification = ProductClassification::query()->where('id', $this->product_classification_id)->first();
        empty($product_classification) ? $this->handleCreateOperation($aCsv) : $this->handleUpdateOperation($aCsv, $product_classification);

        App::setLocale($this->default_locale);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)->map(function ($item, $index) use ($aCsv) {
            if ($item->field_or_attribute_name === 'id') {
                $this->product_classification_id = $aCsv[$index];
            }
        });
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->product_classification = new ProductClassification;

        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->product_classification = $entity;
        $this->saveDataToDatabase($aCsv);
    }

    protected function saveDataToDatabase($aCsv): void
    {
        DB::transaction(function () {
            $this->handleColumnMapping();
            $this->product_classification->save();
        });
    }

    protected function mapToField($item, $value): void
    {
        $this->product_classification->{$item->field_or_attribute_name} = $value;
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        //
    }
}
