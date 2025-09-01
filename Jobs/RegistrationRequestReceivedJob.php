<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Services\EmailService;
use Amplify\System\Backend\Services\MessageService;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use App\Models\Customer;
use App\Models\Event;
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
        $customer = Customer::with('addresses', 'industryClassification')->find($this->customerId);

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->registrationRequestEmailToCustomer($eventAction, $customer);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->registrationRequestMessageToCustomer($eventAction->eventTemplate, $customer);
            }
        }
    }
}
