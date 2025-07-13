<?php

namespace Amplify\System\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contact $contact_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($contact_id)
    {
        $this->contact_id = $contact_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
