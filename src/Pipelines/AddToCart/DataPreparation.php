<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Collections\WarehouseCollection;
use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Contracts\AddToCart;
use Illuminate\Support\Str;

class DataPreparation implements AddToCart
{
    private WarehouseCollection $warehouses;

    public function __construct()
    {
        $this->warehouses = ErpApi::getWarehouses();

    }

    public function handle(array $data, \Closure $next)
    {
        try {

            $dbProducts = Product::with('productImage')
                ->whereIn('product_code', collect($data['items'])->pluck('product_code')->toArray())
                ->get();

            $data['meta']['products'] = $dbProducts;

            $fallbackImage = config('amplify.frontend.fallback_image_path');

            if (!Str::contains($fallbackImage, 'http')) {
                $fallbackImage = asset($fallbackImage);
            }

            foreach ($data['items'] ?? [] as $index => $item) {
                if (!isset($item['additional_info'])) {
                    $item['additional_info'] = [];
                }
                /**
                 * @var Product $dbProduct
                 */
                $dbProduct = $dbProducts->firstWhere('product_code', $item['product_code']);

                if ($dbProduct) {
                    $product['product_id'] = $dbProduct->getKey();
                    $product['product_code'] = $dbProduct->product_code;
                    $product['quantity'] = $item['qty'] ?? $item['quantity'] ?? $dbProduct->min_order_qty;
                    $product['uom'] = $dbProduct->uom ?? 'EA';
                    $product['unitprice'] = null;
                    $product['subtotal'] = null;
                    $product['address_id'] = customer_check() ? customer(true)->customer_address_id : null;
                    $product['product_name'] = $dbProduct->product_name;
                    $product['product_back_order'] = $dbProduct->allow_back_order ?? false;
                    $product['product_image'] = $dbProduct->productImage?->main ?? $fallbackImage;
                    $product['source_type'] = $item['source_type'] ?? 'Default';
                    $product['source'] = $item['source'] ?? 'Default';
                    $product['expiry_date'] = $item['expiry_date'] ?? null;
                    $product['additional_info'] = [
                        ...$item['additional_info'],
                        'minimum_quantity' => $dbProduct->min_order_qty ?? 1,
                        'quantity_interval' => $dbProduct->qty_interval ?? 1,
                        'own_truck_only' => $dbProduct->own_truck_only ?? false,
                        'is_non_stock' => $dbProduct->is_non_stock ?? false,
                        'in_stock' => $dbProduct->in_stock ?? false,
                        'is_ncnr' => $dbProduct->is_ncnr ?? false,
                        'ship_restriction' => $dbProduct->ship_restriction ?? null,
                        'item_restricted' => false,
                    ];

                    $product['product_warehouse_code'] = $this->getProductWarehouse($item);
                    $warehouse = $this->warehouses->firstWhere('WarehouseNumber', $product['product_warehouse_code']);
                    $product['warehouse_id'] = $warehouse->InternalId ?? null;

                    $data['items'][$index] = $product;
                } else {
                    $data['errors'][$index][] = __('The product :code does not exist in system.', ['code' => $item['product_code']]);
                }
            }

        } catch (\Exception $exception) {
            abort(500, $exception->getMessage());
        }

        return $next($data);
    }

    private function getProductWarehouse(array $item = [])
    {
        $warehouseCode = $item['product_warehouse_code'];

        if (!empty($warehouseCode)) {
            return $warehouseCode;
        }

        if (!customer_check()) {
            return config('amplify.frontend.guest_checkout_warehouse');
        }

        return ErpApi::getCustomerDetail()->DefaultWarehouse;
    }
}
