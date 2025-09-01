<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegistrationRequestAcceptedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $customerId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $customer_id)
    {
        $this->eventCode = $event_code;
        $this->customerId = $customer_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();
        $customer = Customer::find($this->customerId);

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->registrationRequestAcceptedEmailToCustomer($eventAction, $customer);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->registrationRequestAcceptedMessageToCustomer($eventAction->eventTemplate, $customer);
            }
        }
    }
}
