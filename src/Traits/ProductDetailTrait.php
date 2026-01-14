<?php

namespace Amplify\System\Traits;

use Amplify\Frontend\Http\Controllers\DynamicPageLoadController;
use Amplify\System\Backend\Models\CategoryProduct;
use Amplify\System\Backend\Models\DocumentTypeProduct;
use Amplify\System\Sayt\Classes\RemoteResults;
use Amplify\System\Sayt\Facade\Sayt;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ProductDetailTrait
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ErrorException
     */
    private function getProductFromEasyAsk($product_id): RemoteResults
    {
        return Sayt::storeProductDetail($product_id, request()->route('seo_path'));
    }

    /**
     * @return mixed
     */
    private function getCategories($Product_Id)
    {
        return CategoryProduct::select(['categories.id', 'categories.category_name'])
            ->where('product_id', $Product_Id)
            ->join('categories', 'category_product.category_id', 'categories.id')
            ->get();
    }

    /**
     * @return mixed
     */
    private function getDocuments($Product_Id)
    {
        return DocumentTypeProduct::with('documentType')
            ->where('product_id', $Product_Id)
            ->orderBy('order', 'ASC')
            ->get();
    }

    private function getSingleProductMetas($isSkuProduct, $Product)
    {
        DynamicPageLoadController::$metaInfo = [
            [
                'content' => $isSkuProduct ?
                    assets_image($Product->Sku_ProductImage ?? '')
                    : assets_image(optional($Product)->Product_Image ?? ''),

                'property' => 'og:image',
                'name' => 'image',
            ], [
                'content' => $isSkuProduct ?
                    assets_image($Product->Sku_ProductImage ?? '')
                    : assets_image(optional($Product)->Product_Image ?? ''),

                'property' => 'og:image:secure_url',
                'name' => 'secure_url',
            ], [
                'content' => $Product->Product_Name ?? '',
                'property' => 'og:title',
                'name' => 'title',
            ], [
                'content' => $Product->Product_Description ?? '',
                'property' => 'og:description',
                'name' => 'description',
            ], [
                'content' => url()->current(),
                'property' => 'og:url',
                'name' => 'url',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getFilteredAttributes($product): Collection
    {
        if (! empty($product->attributes)) {
            $filteredKeys = ['Item #', 'Item Name', 'CADPartID', 'Description'];

            return $product->attributes->filter(fn ($attribute) => ! in_array($attribute->local_name, $filteredKeys));
        }

        return collect([]);
    }

    private function formatDBProductToEasyask($dbProduct)
    {

        return (object) [
            'Product_Id' => $dbProduct->id,
            'Product_Type' => 'normal',
            'Product_Code' => $dbProduct->product_code,
            'Product_Image' => $dbProduct->productImage?->main,
            'Product_Name' => $dbProduct->local_product_name,
            'Status' => 'incomplete',
            'Brand_Name' => 'string',
            'GTIN' => $dbProduct->gtin_number,
            'HasImage' => ! empty($dbProduct->productImage) && $dbProduct->productImage->count() > 0,
            'Product_Description' => $dbProduct->local_description,
            'Product_Slug' => $dbProduct->product_slug,
            'Manufacturer' => ! empty($dbProduct->manufacturer_relation) ? $dbProduct->manufacturer_relation->name : '',
            'MPN' => $dbProduct->manufacturer,
            'Price' => null,
            'Msrp' => null,
            'Days_Published' => $dbProduct->published_at,
            'Sku_Id' => $dbProduct->sku_id,
            'Sku_ProductCode' => null,
            'Sku_ProductImage' => null,
            'Sku_Name' => null,
            'Sku_Status' => null,
            'EAScore' => null,
            'EASource' => null,
            'EAWeight' => null,
            'EARules' => null,
            'Sku_Count' => 1,
            'Sku_List' => [],
            'MinPrice' => null,
            'MaxPrice' => null,
        ];
    }
}
