<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Amplify\System\Services\EmailService;
use Amplify\System\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @property EmailService $emailService
 * @property MessageService $messageService
 * @property Event $eventInfo
 */
class RegistrationRequestReceivedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $customerId;
    public $contactId;

    /**
     * Create a new job instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct($event_code, $args = [])
    {
        $this->eventCode = $event_code;
        $this->customerId = $args['customer_id'] ?? null;
        $this->contactId = $args['contact_id'] ?? null;

        if (empty($this->customerId) || empty($this->contactId)) {
            throw new \Exception("Customer ID or Contact ID is required");
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();
        $customer = Customer::with('addresses', 'industryClassification')->find($this->customerId);
        $contact = Contact::find($this->contactId);

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->registrationRequestEmailToCustomer($eventAction, $customer, $contact);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->registrationRequestMessageToCustomer($eventAction->eventTemplate, $customer, $contact);
            }
        }
    }
}
