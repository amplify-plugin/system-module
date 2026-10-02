<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateShipping
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // Resolve shipping method and calculate its actual charge.
        // Do not trust shipping.charge.amount from the browser.

        $context->calculated['shipping'] = '0.00';

        return $next($context);
    }
}
