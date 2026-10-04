<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Contexts\CheckoutContext;
use Amplify\System\Contracts\Checkout;
use Closure;

final class ResolveCustomer implements Checkout
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $channel = $context->payload['checkout']['channel'];

        $customer = match ($channel) {
            'web' => customer_check() ? customer() : null,
            'admin', 'api' => Customer::find($context->payload['customer']['id']),
            default => null,
        };

        $context->resolved['customer'] = $customer;

        if ($context->resolved['customer']) {
            $context->payload['customer']['id'] = $customer?->id;
        }

        return $next($context);
    }
}
