<?php

namespace Amplify\System\Jobs;

use Amplify\System\Utility\Models\ImportJob;
use Amplify\System\Utility\Repositories\Interfaces\ImportJobInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JsonException;

class ParentImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $request;

    public ImportJobInterface $importJob;

    public $className;

    public int $importJobId;

    public bool $isImportJob = true;

    public string $jobType = 'ImportJob';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
        $this->importJobId = $request['import_job_id'];
        $this->className = get_class($this);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ImportJobInterface $importJob)
    {
        echo PHP_EOL, PHP_EOL, '## ParentImportJob :: handle() ##', PHP_EOL, PHP_EOL;

        $this->importJob = $importJob;
        $this->importJob->processImportJob($this->request);
    }

    /**
     * @throws JsonException
     */
    public function failed($exception)
    {
        echo "## $this->className :: failed() ##", PHP_EOL, PHP_EOL;

        $exceptionMsg = $exception->getMessage();
        $error = [
            'job_name' => $this->className,
            'message' => $exceptionMsg,
        ];
        $this->handleFailedJobs($error);
    }

    /**
     * @throws JsonException
     */
    public function handleFailedJobs($error): void
    {
        echo "## $this->className :: handleFailedJobs() ##", PHP_EOL, PHP_EOL;

        $importJob = ImportJob::query()->find($this->importJobId);
        $importJob->status = 'failed';
        /*$importJob->failed_count  = 1;
        $importJob->success_count = 0;*/
        $importJob->errors = json_encode([$error], JSON_THROW_ON_ERROR);
        $importJob->save();
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
