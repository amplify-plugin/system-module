<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateShipping
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $context->calculated['shipping'] = data_get($context->payload, 'amounts.shipping', null);
        $context->payload['amounts']['shipping'] = $context->calculated['shipping'];

        return $next($context);
    }
}
