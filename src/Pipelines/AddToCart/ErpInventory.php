<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Contracts\AddToCart;

class ErpInventory implements AddToCart
{
    /**
     * @param array $data
     * @param \Closure $next
     * @return mixed
     */
    public function handle(array $data, \Closure $next)
    {
        abort_if(!ErpApi::enabled(), 500, __('ERP Service is not enabled.'));

        $items = collect($data['items'] ?? []);

        $warehouseString = $items->pluck('product_warehouse_code')->implode(',');

        $productPriceAvailability = ErpApi::getProductPriceAvailability([
            'warehouse' => $warehouseString,
            'items' => $items->map(fn($i) => ['item' => $i['product_code'], 'uom' => $i['uom'], 'qty' => $i['quantity']])->toArray()
        ]);

        $data['meta']['inventories'] = $productPriceAvailability->map(function ($p) {
            unset($p->Warehouses);
            return $p;
        });

        abort_if($productPriceAvailability->isEmpty(), 500, product_not_avail_message());

        $missingEntries = [];

        foreach ($data['items'] ?? [] as $index => $item) {

            $inventory = $productPriceAvailability
                ->where('ItemNumber', '=', $item['product_code'])
                ->where('WarehouseID', '=', $item['product_warehouse_code'])
                ->first();

            if ($inventory) {
                $item['uom'] = $inventory->UnitOfMeasure ?? 'EA';
                $item['unitprice'] = $inventory->OrderPrice;
                $item['subtotal'] = $inventory->ExtendedPrice;
                $item['product_back_order'] = $inventory->AllowBackOrder ?? $item['product_back_order'] ?? false;
                $item['additional_info']['minimum_quantity'] = $inventory->MinOrderQuantity ?? $item['additional_info']['minimum_quantity'];
                $item['additional_info']['quantity_interval'] = $inventory->QuantityInterval ?? $item['additional_info']['quantity_interval'];
                $item['additional_info']['quantity_available'] = $inventory->QuantityAvailable ?? 0;
                $item['additional_info']['item_restricted'] = $inventory->ItemRestricted ?? false;

                $data['items'][$index] = $item;
            } else {
                $missingEntries[] = $item['product_code'];
            }
        }

        abort_if(!empty($missingEntries), 500, product_not_avail_message());


        return $next($data);
    }
}
