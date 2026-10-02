<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateCheckoutVersion
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // Compare the submitted version with the server-side checkout/cart version.
        // Reject if prices, inventory, cart, or other authoritative state changed.

        return $next($context);
    }
}
