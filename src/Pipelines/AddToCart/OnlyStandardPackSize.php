<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\System\Contracts\AddToCart;

class OnlyStandardPackSize implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        if (config('amplify.pim.use_minimum_order_quantity', false)) {
            foreach ($data['items'] as $item) {
                $interval = $item['additional_info']['quantity_interval'] ?? 1;
                $ordQty = $item['quantity'];
                abort_if(
                    $ordQty % $interval != 0,
                    500,
                    __('Product :code requires a order pack(s) of :interval. You entered :ordQty.', ['code' => $item['product_code'], 'interval' => $interval, 'ordQty' => $ordQty])
                );
            }
        }

        return $next($data);
    }
}
