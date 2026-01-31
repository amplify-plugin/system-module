<?php

namespace Amplify\System\Listeners;

use Illuminate\Queue\Events\JobProcessing;

class QueueBeforeListener
{
    /**
     * Handle the event.
     */
    public function handle(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $command = unserialize($payload['data']['command'] ?? '');

        if ($command->isImportJob ?? false) {
            $isFinalJob = $command->isFinalJob ?? false;
            $uuid = $event->job->uuid();
            manageImportJobHistory($uuid, $command->importJobId, $isFinalJob, 'create', 'processing');
        }
    }
}
