<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateSubtotal
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $subtotal = 0.0;

        foreach ($context->payload['items'] as $item) {
            // Replace with server-resolved price.
            $price = 0.0;
            $subtotal += $price * (int) $item['quantity'];
        }

        $context->calculated['subtotal'] = number_format($subtotal, 2, '.', '');

        return $next($context);
    }
}
