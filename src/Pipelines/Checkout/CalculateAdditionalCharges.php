<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateAdditionalCharges
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // ERP/configuration driven charges.
        $context->calculated['additional'] = [];

        return $next($context);
    }
}
