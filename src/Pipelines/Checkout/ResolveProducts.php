<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ResolveProducts
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // Replace with your product/catalog service.
        // Never use client price, warehouse, or product metadata as authoritative.
        $context->resolved['products'] = [];

        return $next($context);
    }
}
