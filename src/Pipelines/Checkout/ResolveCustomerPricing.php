<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Wrappers\ProductPriceAvailability;
use Amplify\System\Contexts\CheckoutContext;
use Closure;
use Illuminate\Validation\ValidationException;

final class ResolveCustomerPricing
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $items = collect($context->payload['items'] ?? []);

        $warehouseString = $items->pluck('product_warehouse_code')->implode(',');

        $erpPricing = ErpApi::getProductPriceAvailability([
            'customer_number' => $context->payload['customer']['number'],
            'warehouse' => $warehouseString,
            'items' => $items->map(fn($i) => ['item' => $i['product_code'], 'uom' => $i['uom'], 'qty' => $i['quantity']])->toArray(),
        ]);

        if ($erpPricing->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('The items are invalid. Please contact your representative, email us at' .
                    ' <a href="mailto::email">:email</a>, or call us at <a href="tel::phone">:phone</a>.',
                    [
                        'email' => config('amplify.cms.email'),
                        'phone' => config('amplify.cms.phone'),
                    ]
                ),
            ]);
        }

        $missing = [];

        $items = $items->map(function ($item) use (&$erpPricing, &$missing) {

            /**
             * @var ProductPriceAvailability $inventory
             */
            $inventory = $erpPricing
                ->where('ItemNumber', '=', $item['product_code'])
                ->where('WarehouseID', '=', $item['product_warehouse_code'])
                ->first();

            if ($inventory) {
                $item['unit_code'] = $inventory->UnitOfMeasure ?? 'EA';
                $item['ssp'] = $inventory->OrderPrice;
                $item['customer_price'] = $inventory->ExtendedPrice;
            } else {
                $missing[] = $item['product_code'];
            }

            return $item;
        });

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'items' => __('The items (:codes) are invalid. Please contact your representative, email us at' .
                    ' <a href="mailto::email">:email</a>, or call us at <a href="tel::phone">:phone</a>.',
                    [
                        'email' => config('amplify.cms.email'),
                        'phone' => config('amplify.cms.phone'),
                        'codes' => implode(', ', $missing),
                    ]
                ),
            ]);
        }

        $context->resolved['prices'] = $erpPricing->map(function ($p) {
            unset($p->Warehouses);
            return $p;
        });

        $context->payload['items'] = $items->toArray();

        return $next($context);
    }
}
