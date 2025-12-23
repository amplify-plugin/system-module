<?php

namespace Amplify\System\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $syncData = [])
    {
    }

}
