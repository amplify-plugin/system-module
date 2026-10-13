<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateTotal
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $total = array_sum($context->calculated);

        $context->calculated['total'] = $total;

        $context->payload['amounts']['total'] = $total;

        return $next($context);
    }
}
