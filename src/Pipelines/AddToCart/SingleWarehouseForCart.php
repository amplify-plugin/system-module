<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Contracts\AddToCart;

class SingleWarehouseForCart implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        $warehouses = collect($data['items'] ?? [])->pluck('product_warehouse_code')->unique()->toArray();

        if (ErpApi::useSingleWarehouseInCart()) {
            abort_if(count($warehouses) > 1, __('Adding item(s) to cart from multiple warehouses not allowed.'));
        }

        return $next($data);
    }
}
