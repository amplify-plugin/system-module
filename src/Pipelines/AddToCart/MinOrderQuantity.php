<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\System\Contracts\AddToCart;

class MinOrderQuantity implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        if (config('amplify.pim.use_minimum_order_quantity', false)) {
            foreach ($data['items'] as $index => $item) {
                $minQty = $item['additional_info']['minimum_quantity'] ?? 1;
                $ordQty = $item['quantity'];

                if ($ordQty < $minQty) {
                    $data['errors'][$index][] = __('The product :code requires a minimum order quantity of :minQty. You entered :ordQty.', ['code' => $item['product_code'], 'minQty' => $minQty, 'ordQty' => $ordQty]);
                }
            }
        }

        return $next($data);
    }
}
