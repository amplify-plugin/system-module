<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetPassword implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $otp;

    public $contactId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $arg)
    {
        $this->eventCode = $event_code;
        $this->otp = $arg['otp'];
        $this->contactId = $arg['contact_id'];

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
            $contact = Contact::find($this->contactId);
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->resetPasswordEmailToCustomer($eventAction, $this->otp, $contact);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                // $this->messageService->sendOrderDetailsMessageToCustomer($eventAction->eventTemplate, $order, $customer);
            }
        }
    }
}
