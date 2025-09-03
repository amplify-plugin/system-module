<?php

namespace Amplify\System\Jobs;

use Amplify\System\Services\MessageService;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use App\Models\Contact;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @property \Amplify\System\Services\EmailService $emailService
 * @property MessageService $messageService
 * @property Event $eventInfo
 */
class ContactAccountRequestAcceptedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $contactId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $contact_id)
    {
        $this->eventCode = $event_code;
        $this->contactId = $contact_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();
        $contact = Contact::with('customer')->find($this->contactId);
        // Merge Customer code to Contact->customer_code
        $contact->customer_code = $contact->customer->customer_code;

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->contactAccountRegistrationRequestAcceptedEmail($eventAction, $contact);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->registrationRequestMessageToCustomer($eventAction->eventTemplate, $contact);
            }
        }
    }
}
