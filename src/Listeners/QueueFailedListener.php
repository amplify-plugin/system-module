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
    }
}
