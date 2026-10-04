<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Contexts\CheckoutContext;
use Amplify\System\Contracts\Checkout;
use Closure;

final class ResolveContact implements Checkout
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $channel = $context->payload['checkout']['channel'];

        $contact = match ($channel) {
            'web' => customer_check() ? customer(true) : null,
            'admin', 'api' => Contact::find($context->payload['contact']['id']),
            default => null,
        };

        $context->resolved['contact'] = $contact;

        if ($context->resolved['contact']) {
            $context->payload['contact']['id'] = $contact?->id;
        }

        return $next($context);
    }
}
