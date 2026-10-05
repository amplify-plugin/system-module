<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ResolveProducts
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $items = $context->payload['items'];

        $productIds = array_values(
            array_unique(
                array_column($items, 'product_id')
            )
        );

        $products = Product::whereIn('id', $productIds)->get();

        if ($products->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('The items are invalid. Please contact your representative, email us at'.
                    ' <a href="mailto::email">:email</a>, or call us at <a href="tel::phone">:phone</a>.',
                    [
                        'email' => config('amplify.cms.email'),
                        'phone' => config('amplify.cms.phone'),
                    ]
                ),
            ]);
        }

        if ($products->count() != count($productIds)) {

            $dbIds = $products->pluck('id')->toArray();

            $diff = array_diff($productIds, $dbIds);

            throw ValidationException::withMessages([
                'items' => __('The items with ID (:missing) are not available. Please contact your representative, email us at'.
                    ' <a href="mailto::email">:email</a>, or call us at <a href="tel::phone">:phone</a>.', [
                        'email' => config('amplify.cms.email'),
                        'phone' => config('amplify.cms.phone'),
                        'missing' => array_values($diff),
                    ]),
            ]);
        }

        $context->resolved['items'] = $products;

        return $next($context);
    }
}
