<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WishlistProductRestockedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    public $productCodes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->productCodes = $args;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger()->debug('In Stock Products', [$this->productCodes]);

//        $this->getNecessaryItems();
//        $contact = Contact::where('id', $this->data['contact_id'])->first();
//
//        foreach ($this->eventInfo->eventActions as $eventAction) {
//            if ($eventAction->eventTemplate->notification_type == 'emailable') {
//                $this->emailService->sendWishlistProductRestockedEmail($eventAction, $contact);
//            }
//        }
    }
}
