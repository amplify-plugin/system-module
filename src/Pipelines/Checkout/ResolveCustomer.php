<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ResolveCustomer
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $user = auth()->user();

        if ($user) {
            // Never trust company_id/customer_id from the browser.
            $context->resolved['company_id'] = $user->company_id;
            $context->resolved['customer_id'] = $user->customer_id;
            $context->resolved['customer'] = $user->customer;
        } else {
            $context->resolved['company_id'] = null;
            $context->resolved['customer_id'] = null;
        }

        return $next($context);
    }
}
