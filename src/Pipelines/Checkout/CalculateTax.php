<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateTax
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $context->calculated['tax'] = data_get($context->payload, 'amounts.tax', null);
        $context->payload['amounts']['tax'] = $context->calculated['tax'];

        return $next($context);
    }
}
