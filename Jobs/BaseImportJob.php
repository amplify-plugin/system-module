<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Services\JobFailService;
use Amplify\System\Utility\Models\ImportError;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use JsonException;

abstract class BaseImportJob
{
    public $importDefinition;

    public $userId;

    public $locale;

    public $default_locale;

    public $is_updating;

    public $className;

    public $thisImportData = [];

    public $aCsv;

    public $column_mapping;

    public int $importJobId;

    public bool $isImportJob = true;

    public string $jobType = 'ImportJob';

    public bool $isFinalJob = true;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->aCsv = $data['aCsv'];
        $this->column_mapping = $data['column_mapping'];
        $this->importJobId = $data['importJobId'];
        $this->userId = $data['userId'];
        $this->locale = $data['locale'];
        $this->importDefinition = $data['importDefinition'];
        $this->default_locale = App::getLocale();
        $this->is_updating = false;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Taking a clone of running job that extends this class
        $jobFailService = JobFailService::factory();
        $jobFailService->job = $this->job;

        $this->getMappingProcessed($this->aCsv);
    }

    /**
     * @throws JsonException
     */
    public function failed(\Throwable $exception)
    {
        echo "## $this->className :: failed() ##", PHP_EOL, PHP_EOL;

        $this->handleColumnMapping(true);

        $jobFailService = JobFailService::factory();
        $job = $jobFailService->job;

        ImportError::query()->create([
            'import_job_id' => $this->importJobId,
            'import_data' => json_encode($this->thisImportData, JSON_THROW_ON_ERROR),
            'job_name' => $this->className,
            'uuid' => $job->uuid(),
            'error_message' => $exception->getMessage(),
        ]);
        Schema::enableForeignKeyConstraints();
    }

    /**
     * @param  false  $failed
     */
    protected function handleColumnMapping(bool $failed = false): void
    {
        collect($this->column_mapping)->map(function ($item, $index) use ($failed) {
            if ($failed) {
                $this->thisImportData[$item->column_name] = $this->aCsv[$index] ?? null;
            } else {
                $value = $this->aCsv[$index] ?? null;
                switch ($item->map_to) {
                    case 'Table':
                    case 'Field':
                    case 'Attribute':
                        $this->{"mapTo$item->map_to"}($item, $value);
                        break;
                    default:
                        break;
                }
            }
        });
    }

    /**
     * @return mixed
     */
    abstract protected function getMappingProcessed($aCsv);

    /**
     * @return mixed
     */
    abstract protected function handleCreateOperation($aCsv);

    /**
     * @return mixed
     */
    abstract protected function handleUpdateOperation($aCsv, $entity);

    abstract protected function mapToField($item, $value): void;

    abstract protected function mapToAttribute($item, $value): void;

    abstract protected function mapToTable($item, $value): void;

    abstract protected function prepareInitialProperty($aCsv): void;

    abstract protected function saveDataToDatabase($aCsv): void;
}
