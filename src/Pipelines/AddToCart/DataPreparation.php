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

            $items = collect($data['items']);

            $firstItem = $items->first();

            $dbProducts = Product::with('productImage')
                ->when(!empty($firstItem['product_id']),
                    function ($query) use ($items) {
                        return $query->whereIn('products.id', $items->pluck('product_id')->toArray());
                    }, function ($query) use ($items) {
                        return $query->whereIn('products.product_code', $items->pluck('product_code')->toArray());
                    })
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
                $dbProduct = $dbProducts->firstWhere('product_code', '=', $item['product_code']);

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
                    unset($data['items'][$index]);
                    $data['errors'][$index][] = __('This :code part number is not available on our website. Please contact your representative, email us at <a href="mailto::email">:email</a> , or call us at <a href="tel::phone">:phone.', [
                        'code' => $item['product_code'],
                        'email' => config('amplify.cms.email'),
                        'phone' => config('amplify.cms.phone'),
                    ]);
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
