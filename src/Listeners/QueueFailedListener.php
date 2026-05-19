<?php

namespace Amplify\System\Listeners;

use Illuminate\Queue\Events\JobFailed;

class QueueFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(JobFailed $event)
    {
        $payload = $event->job->payload();
        $command = unserialize($payload['data']['command'] ?? '');

        if ($command->isImportJob ?? false) {
            $isFinalJob = $command->isFinalJob ?? false;
            $uuid = $event->job->uuid();
            manageImportJobHistory($uuid, $command->importJobId, $isFinalJob);
        }

        $exception = new \Error(
            message: "[$event->connectionName] {$event->job->resolveName()} Job Failed. Error: {$event->exception->getMessage()}",
            previous: $event->exception
        );

        report($exception);
    }
}
