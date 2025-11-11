<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;
use Illuminate\Http\Request;

class ErpInventory
{
    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $products = $request->input('products', []);
        $customerDefaultWarehouse = ErpApi::getCustomerDetail()->DefaultWarehouse
            ?? customer()->warehouse()->code
            ?? config('amplify.frontend.guest_checkout_warehouse');



        $erpProductDetails = $this->getERPInfo($request->input('products', []));

        if ($erpProductDetails->isEmpty()) {
            return $this->apiResponse(false, product_not_avail_message(), 404);
        }

        return $next($request);
    }

    /**
     * @return \Amplify\ErpApi\Collections\ProductPriceAvailabilityCollection
     */
    private  function getERPInfo(array|string $codes, int $quantity = 1, $warehouse = null)
    {
        if (is_array($codes)) {
            $items = array_map(function ($item) use (&$itemWarehouse) {
                if (! empty($item['product_warehouse_code'])) {
                    $itemWarehouse = $item['product_warehouse_code'];
                }

                return [
                    'item' => $item['product_code'],
                    'qty' => $item['qty'],
                    'uom' => $item['product_uom'] ?? 'EA',
                ];
            }, $codes);

        } else {
            $items = [
                [
                    'item' => $codes,
                    'qty' => $quantity,
                    'uom' => 'EA',
                ],
            ];

            if ($warehouse) {
                $itemWarehouse = $warehouse;
            }
        }

        return ErpApi::getProductPriceAvailability([
            'items' => $items,
            'warehouse' => $itemWarehouse,
        ]);
    }
}
