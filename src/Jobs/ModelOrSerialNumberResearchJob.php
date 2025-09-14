<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModelOrSerialNumberResearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $contactId;

    private $researchData;

    private $customerInfo;

    private $uploadedFile;

    /**
     * Create a new job instance.
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->researchData = $args['research_data'];
        $this->customerInfo = $args['customer_info'];
        $this->uploadedFile = $args['uploaded_file'];
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->getNecessaryItems();

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->modelOrSerialNumberResearchEmailToAdmin($eventAction, $this->researchData, $this->customerInfo, $this->uploadedFile);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                //
            }
        }
    }
}
