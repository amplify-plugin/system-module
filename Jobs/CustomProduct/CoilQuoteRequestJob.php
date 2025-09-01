<?php

namespace Amplify\System\Jobs\CustomProduct;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CoilQuoteRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $contactId;

    private $coilData;

    private $customerInfo;

    /**
     * Create a new job instance.
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->coilData = $args['coil_data'];
        $this->customerInfo = $args['customer_info'];
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->getNecessaryItems();

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->coilQuoteRequestEmailToAdmin($eventAction, $this->coilData, $this->customerInfo);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                //
            }
        }
    }
}
