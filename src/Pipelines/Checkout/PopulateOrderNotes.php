<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\CustomerOrderNote;
use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class PopulateOrderNotes
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $notes = $context->payload['notes'];

        $notes = array_map(fn ($note) => new CustomerOrderNote($note), $notes);

        $context->resolved['notes'] = $notes;

        return $next($context);
    }
}
