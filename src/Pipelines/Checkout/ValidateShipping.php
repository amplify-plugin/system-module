<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\CustomerAddress;
use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ValidateShipping
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $shipping = $context->payload['shipping'] ?? null;

        if (!$shipping) {
            throw ValidationException::withMessages([
                'shipping' => 'Shipping information is required.',
            ]);
        }

        if ($shipping['number'] != 'TEMP') {

            $address = CustomerAddress::where('address_code', $shipping['number'])->first();

            if (!$address) {
                throw ValidationException::withMessages([
                    'shipping.number' => 'The selected shipping address is invalid.',
                ]);
            }

            $context->resolved['shipping'] = $address;

            $context->payload['shipping']['id'] = $address->id;
        }

        return $next($context);
    }
}
