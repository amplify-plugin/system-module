<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\System\Contracts\AddToCart;

class AllowBackOrder implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        foreach ($data['items'] as $index => $item) {
            $quantityAvailable = (int) ($item['additional_info']['quantity_available'] ?? 0);

            if ($quantityAvailable <= 0) {
                $productAllowsBackOrder = (bool) ($item['product_back_order'] ?? false);
                $customerAllowsBackOrder = (bool) (customer()->allow_backorder ?? false);

                if (! ($productAllowsBackOrder && $customerAllowsBackOrder)) {
                    $data['errors'][$index][] = __('The product :code is currently out of stock and back-ordering is not available.', [
                        'code' => $item['product_code'],
                    ]);
                }
            }
        }

        return $next($data);
    }
}
