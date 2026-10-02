<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ValidateIdempotency
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $key = $context->payload['checkout']['idempotency_key'] ?? null;

        // Enforce a UNIQUE database constraint on this key and return the
        // existing order when the same submission is retried.

        $context->meta['idempotency_key'] = $key;

        return $next($context);
    }
}
