<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateItems
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $items = $context->payload['items'] ?? [];

        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => 'At least one checkout item is required.',
            ]);
        }

        if (count($items) > 900) {
            throw ValidationException::withMessages([
                'items' => 'A maximum of 900 items is allowed.',
            ]);
        }

        return $next($context);
    }
}
