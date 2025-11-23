<?php

namespace Amplify\System\Jobs;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CartPricingSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?Cart $cart;

    public $afterCommit = true;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->cart = Cart::find($id);

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->cart) {

            $this->cart = $this->cart->load('cartItems');

            if (ErpApi::enabled()) {

            }
        }
    }
}
