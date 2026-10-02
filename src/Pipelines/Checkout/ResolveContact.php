<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ResolveContact
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $user = auth()->user();

        if ($user) {
            // Server-owned value; ignore client contact_id.
            $context->resolved['contact_id'] = $user->contact_id;
        } else {
            $context->resolved['contact_id'] = null;
        }

        return $next($context);
    }
}
