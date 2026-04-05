<?php

namespace Amplify\System\Pipelines\ProductDetail;

use Amplify\System\Contracts\ProductDetail;
use Illuminate\Database\Eloquent\Builder;

class SelectColumns implements ProductDetail
{
    public function handle(Builder $query, \Closure $next)
    {
        $query->select('id', 'product_name', 'flags', 'has_sku', 'description', 'short_description', 'manufacturer_id',
            'single_product_page_id', 'sku_default_attributes', 'features', 'specifications', 'min_order_qty', 'vendornum',
            'qty_interval', 'product_code', 'in_stock', 'is_ncnr', 'gtin_number', 'product_slug', 'manufacturer', 'sku_id', 'uom')
            ->with('attributes', 'productImage', 'manufacturerRelation', 'singleProductPage', 'brand');

        return $next($query);
    }
}
