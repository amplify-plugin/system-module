<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderAcceptedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $orderId;

    public $customerId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $order_id, $customer_id)
    {
        $this->eventCode = $event_code;
        $this->orderId = $order_id;
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
        $order = CustomerOrder::find($this->orderId);
        $customer = Customer::find($this->customerId);

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->sendOrderDetailsEmailToCustomer($eventAction, $order, $customer);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->sendOrderDetailsMessageToCustomer($eventAction->eventTemplate, $order, $customer);
            }
        }
    }
}
