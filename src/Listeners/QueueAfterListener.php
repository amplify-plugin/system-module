<?php

namespace Amplify\System\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;

class QueueAfterListener
{
    /**
     * Handle the event.
     */
    public function handle(JobProcessed $event)
    {
        $payload = $event->job->payload();
        $command = unserialize($payload['data']['command'] ?? '');

        if ($command->isImportJob ?? false) {
            $isFinalJob = $command->isFinalJob ?? false;
            $uuid = $event->job->uuid();
            $exists = DB::table('failed_jobs')->where('uuid', $uuid)->exists();

            if (! $exists) {
                manageImportJobHistory($uuid, $command->importJobId, $isFinalJob, 'update', 'success');
            }
        }
    }
}
