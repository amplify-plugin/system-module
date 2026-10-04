<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Support\Facades\DB;

final class PopulateOrderNotes
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $order = DB::transaction(function () use ($context) {
            // Build the order exclusively from resolved/calculated server data.
            //
            // Example:
            // $order = Order::create([
            //     'company_id' => $context->resolved['company_id'],
            //     'contact_id' => $context->resolved['contact_id'],
            //     'subtotal' => $context->calculated['subtotal'],
            //     'tax' => $context->calculated['tax'],
            //     'shipping' => $context->calculated['shipping'],
            //     'total' => $context->calculated['total'],
            // ]);

            return null; // Replace with your Order model.
        });

        $context->resolved['order'] = $order;

        return $next($context);
    }
}
