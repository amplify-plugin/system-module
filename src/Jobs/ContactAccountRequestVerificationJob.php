<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Amplify\System\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @property \Amplify\System\Services\EmailService $emailService
 * @property MessageService $messageService
 * @property \Amplify\System\Backend\Models\Event $eventInfo
 */
class ContactAccountRequestVerificationJob implements ShouldQueue
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
        $contact = Contact::find($this->contactId);

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->contactAccountRequestVerificationEmail($eventAction, $contact);
            }
        }
    }
}
