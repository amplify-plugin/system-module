<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use App\Models\CustomerOrderNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderNotesUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $customerOrderNoteId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $customer_order_note_id)
    {
        $this->eventCode = $event_code;
        $this->customerOrderNoteId = $customer_order_note_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getNecessaryItems();
        $customerOrderNote = CustomerOrderNote::find($this->customerOrderNoteId);
        $order = $customerOrderNote->order;

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->updateOrderNoteEmailToCustomer($order, $customerOrderNote->note, $eventAction);
            }

            if ($eventAction->eventTemplate->notification_type == 'messageable') {
                $this->messageService->updateOrderNoteMessageToCustomer($order, $customerOrderNote->note, $eventAction->eventTemplate);
            }
        }
    }
}
