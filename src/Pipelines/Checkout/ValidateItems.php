<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateItems
{
    private int $maxItems = 900;

    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $items = $context->payload['items'] ?? [];

        $channel = $context->payload['checkout']['channel'] ?? 'web';

        if (count($items) === 0 && in_array($channel, ['api', 'admin'])) {
            throw ValidationException::withMessages([
                'items' => 'The items must have at least 1 in checkout.',
            ]);
        }

        if ($channel === 'web') {
            $cart = getCart();
            $items = $cart->cartItems->toArray();
        }

        if (count($items) > $this->maxItems) {
            throw ValidationException::withMessages([
                'items' => "The items must not have more than {$this->maxItems} items per checkout.",
            ]);
        }

        $context->payload['items'] = $items;

        return $next($context);
    }
}
