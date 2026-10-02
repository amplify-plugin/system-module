<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateBilling
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $billing = $context->payload['billing'] ?? null;

        if (!$billing) {
            throw ValidationException::withMessages([
                'billing' => 'Billing information is required.',
            ]);
        }

        return $next($context);
    }
}
