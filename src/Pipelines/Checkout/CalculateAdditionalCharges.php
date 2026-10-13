<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CalculateAdditionalCharges
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        foreach ($context->payload['amounts']['additional'] ?? [] as $additional) {
            $context->calculated[$additional['field']] = floatval($additional['value']);
        }

        return $next($context);
    }
}
