<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateTax
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // Call the configured tax service/ERP integration.

        $context->calculated['tax'] = '0.00';

        return $next($context);
    }
}
