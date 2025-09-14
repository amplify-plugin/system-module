<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Amplify\System\OrderRule\Models\CustomerOrderRuleTrack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderRequestRejectedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $contactId;

    private $orderRuleId;

    private $approvalStatus;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $arg)
    {
        $this->eventCode = $event_code;
        $this->contactId = $arg['contact_id'];
        $this->orderRuleId = $arg['order_rule_id'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();

        foreach ($this->eventInfo?->eventActions ?? [] as $eventAction) {
            $contact = Contact::findOrFail($this->contactId);
            $customerOrderRuleTrack = CustomerOrderRuleTrack::findOrFail($this->orderRuleId);

            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->orderRequestRejectedEmailToCustomer($eventAction, $contact, $customerOrderRuleTrack);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                //
            }
        }
    }
}
