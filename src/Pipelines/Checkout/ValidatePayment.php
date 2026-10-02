<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ValidatePayment
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $payment = $context->payload['payment'] ?? [];

        // Resolve the configured gateway driver and validate its token.
        // Never store raw card/account credentials.

        return $next($context);
    }
}
