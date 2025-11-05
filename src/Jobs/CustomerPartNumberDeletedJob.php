<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerPartNumberDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->data = $args;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->getNecessaryItems();
        $data = [
            'customer' => Customer::find($this->data['customer_id']),
            'product' => Product::find($this->data['product_id']),
            ...$this->data
        ];

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->sendCustomerPartNumberDeletedNotification($eventAction, $data);
            }
        }
    }
}
