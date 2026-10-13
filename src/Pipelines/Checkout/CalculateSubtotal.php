<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateSubtotal
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $context->calculated['subtotal'] = collect($context->payload['items'])->sum('subtotal');
        $context->payload['amounts']['subtotal'] = $context->calculated['subtotal'];

        return $next($context);
    }
}
