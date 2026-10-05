<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateApiVersion
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $version = $context->payload['checkout']['version'] ?? null;

        if (! $version) {
            throw ValidationException::withMessages([
                'checkout.version' => 'The checkout version must be set.',
            ]);
        }

        return $next($context);
    }
}
