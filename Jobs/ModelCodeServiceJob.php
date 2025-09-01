<?php

namespace Amplify\System\Jobs;

use App\Models\ModelCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ModelCodeServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $modelCode;

    public $modelCodeId;

    public $modelCodeCode;

    public $modelCodeName;

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

        $modelCode = ModelCode::query()->where('id', $this->modelCodeId)->orWhere('code', $this->modelCodeCode)->first();

        empty($modelCode) ? $this->handleCreateOperation($aCsv) : $this->handleUpdateOperation($aCsv, $modelCode);

        App::setLocale($this->default_locale);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->field_or_attribute_name === 'id') {
                    $this->modelCodeId = $aCsv[$index];
                }

                if ($item->field_or_attribute_name === 'code') {
                    $this->modelCodeCode = $aCsv[$index];
                }

                if ($item->field_or_attribute_name !== 'Ignore' && $item->field_name === 'name') {
                    $this->modelCodeName = $aCsv[$index];
                }
            });
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->modelCode = new ModelCode;

        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->modelCode = $entity;
        $this->is_updating = true;
        $this->saveDataToDatabase($aCsv);
        $this->is_updating = false;
    }

    protected function saveDataToDatabase($aCsv): void
    {
        DB::transaction(function () {
            $this->handleColumnMapping();
            $this->modelCode->save();
        });
    }

    protected function mapToField($item, $value): void
    {
        $this->modelCode->{$item->field_or_attribute_name} = $value;
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
