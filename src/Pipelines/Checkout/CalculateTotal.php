<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateTotal
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $subtotal = (float) ($context->calculated['subtotal'] ?? 0);
        $shipping = (float) ($context->calculated['shipping'] ?? 0);
        $tax = (float) ($context->calculated['tax'] ?? 0);

        $additional = collect(
            $context->calculated['additional'] ?? []
        )->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));

        $context->calculated['total'] = number_format(
            $subtotal + $shipping + $tax + $additional,
            2,
            '.',
            ''
        );

        return $next($context);
    }
}
