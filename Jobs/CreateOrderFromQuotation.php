<?php

namespace Amplify\System\Jobs;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateOrderFromQuotation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $quoteId;

    private $contactId;

    private $additionalInfo;

    /**
     * Create a new job instance.
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->quoteId = $args['quote_id'];
        $this->contactId = $args['customer_id'];
        $this->additionalInfo = $args['additional_info'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->getNecessaryItems();
        $contact = Contact::find($this->contactId);
        $quotation = ErpApi::getQuotationDetail(['quote_number' => 760709, 'customer_number' => $contact->contact_code]);

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->sendOrderFromQuotationNotification($eventAction, $quotation, $contact, $this->additionalInfo);
            }
        }
    }
}
