<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Services\JobFailService;
use Amplify\System\Utility\Models\DataTransformation;
use Amplify\System\Utility\Models\DataTransformationError;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DataTransformationParentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data_transformation_id;

    public $user_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data_transformation_id, $user_id)
    {
        $this->data_transformation_id = $data_transformation_id;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /* Get data transformation by id */
        $dataTransformation = DataTransformation::query()
            ->where('id', $this->data_transformation_id)
            ->firstOrFail();

        /* Prepare data for transformation */
        $scriptsArr = preg_split("/\r\n|\n|\r/", $dataTransformation->scripts);
        $products_list = json_decode(
            Storage::get($dataTransformation->file_path),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
        $data = [
            'scriptsArr' => $scriptsArr,
            'appliesTo' => json_decode($dataTransformation->applies_to, false, 512, JSON_THROW_ON_ERROR)->name,
            'scriptType' => 'run_script',
            'userId' => $this->user_id,
        ];

        /* Making data transformation by dispatching 'ExecuteScriptJob' */
        $products_list_collection = collect($products_list);
        $dataTransformation->update([
            'row_count' => $products_list_collection->count(),
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'processing',
        ]);

        $chunked_products_list_collection = $products_list_collection->chunk(500);
        foreach ($chunked_products_list_collection as $chunk) {
            $chunk->map(function ($item) use ($data, $dataTransformation) {
                ExecuteScriptJob::dispatch((array) $item, $data, $dataTransformation);
            });
        }
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

        $this->dataTransformation->update([
            'errors' => $exception->getMessage(),
            'status' => 'failed',
        ]);

        DataTransformationError::create([
            'data_transformation_id' => $this->dataTransformation->id,
            'data_transformation' => json_encode($this->productData, JSON_THROW_ON_ERROR),
            'job_name' => get_class($this),
            'uuid' => $job->uuid(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}
