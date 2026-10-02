<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ResolveCustomerPricing
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // Resolve price from Amplify customer pricing / ERP.
        // Ignore any client-submitted price.
        $context->resolved['prices'] = [];

        return $next($context);
    }
}
