<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateShipping
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $shipping = $context->payload['shipping'] ?? null;

        if (!$shipping) {
            throw ValidationException::withMessages([
                'shipping' => 'Shipping information is required.',
            ]);
        }

        return $next($context);
    }
}
