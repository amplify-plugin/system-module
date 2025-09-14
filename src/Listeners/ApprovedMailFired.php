<?php

namespace Amplify\System\Listeners;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Events\OrderApproved;

class ApprovedMailFired
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(OrderApproved $event)
    {
        $contact = Contact::findOrFail($event->contact_id);
        // call the notifaction
    }
}
