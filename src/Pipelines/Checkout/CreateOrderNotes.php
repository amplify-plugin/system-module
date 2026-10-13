<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\CustomerOrderNote;
use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CreateOrderNotes
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $context->resolved['notes'] = array_map(fn($note) => new CustomerOrderNote($note), $context->payload['notes']);

        $order = $context->resolved['order'];

        $order->orderNotes()->saveMany($context->resolved['notes']);

        return $next($context);
    }
}
