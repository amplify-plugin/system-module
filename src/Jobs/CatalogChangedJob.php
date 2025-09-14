<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CatalogChangedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $productSyncInfo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $productSyncInfo)
    {
        $this->eventCode = $event_code;
        $this->productSyncInfo = $productSyncInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->catalogChangedEmailToAdmin($eventAction, $this->productSyncInfo);
            }

            // if ($eventAction->eventTemplate->notification_type == 'messageable') {
            //     $this->messageService->catalogChangedMessageToAdmin($eventAction, $this->productSyncInfo);
            // }
        }
    }
}
