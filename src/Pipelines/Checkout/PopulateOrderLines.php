<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\CustomerOrderLine;
use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class PopulateOrderLines
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $items = $context->payload['items'];

        $items = array_map(function ($item) {
            return new CustomerOrderLine([
                'qty' => $item['quantity'] ?? null,
                'unit_code' => $item['uom'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'product_code' => $item['product_code'] ?? null,
                'options' => [
                    'warehouse_code' => $item['product_warehouse_code'] ?? null,
                    'unit_price' => $item['unitprice'] ?? null,
                    'product_back_order' => $item['product_back_order'] ?? 0,
                    'product_name' => $item['product_name'] ?? null
                ],
                'warehouse_id' => $item['warehouse_id'] ?? null,
                'shipping_cost' => $item['shipping_cost'] ?? null,
                'ssp' => $item['ssp'] ?? null,
                'discount_amount' => $item['discount_amount'] ?? null,
                'customer_price' => $item['subtotal'] ?? null,
                'source_type' => $item['source_type'] ?? null,
                'source' => $item['source'] ?? null,
                'additional_info' => $item['additional_info'] ?? (object)[]
            ]);
        }, $items);

        $context->resolved['items'] = $items;

        return $next($context);
    }
}
