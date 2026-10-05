<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ValidateOrderThreshold
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $clientTotal = (float) data_get(
            $context->payload,
            'amounts.client.total',
            0
        );

        $serverTotal = (float) ($context->calculated['total'] ?? 0);

        // Client total is informational only. Your production implementation
        // may compare it for change detection, but must always persist/use
        // the server-calculated total.
        $context->meta['client_total'] = $clientTotal;
        $context->meta['server_total'] = $serverTotal;

        return $next($context);
    }
}
