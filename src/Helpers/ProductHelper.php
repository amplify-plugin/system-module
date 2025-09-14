<?php

namespace Amplify\System\Helpers;

class ProductHelper
{
    public static function checkIfProductIsReadyToPublish(): bool
    {
        $status = false;
        if (is_auto_publish()) {
            // ToDo: Check here if all required fields by Client is filled
            if (
                (isset(request()->ean_number) && ! empty(request()->ean_number))
                && (isset(request()->gtin_number) && ! empty(request()->gtin_number))
                && (isset(request()->upc_number) && ! empty(request()->upc_number))
                && (isset(request()->asin) && ! empty(request()->asin))
                && (isset(request()->manufacturer) && ! empty(request()->manufacturer))
                && (isset(request()->model_code) && ! empty(request()->model_code))
                && (isset(request()->model_name) && ! empty(request()->model_name))
                && isset(request()->manufacturer)
                && self::checkIfAttributesAreMandatoryAndExists(request()->pivot['productAttributes'])
                && isset(request()->selling_price)
                && isset(request()->msrp)
                && isset(request()->main)
                && isset(request()->thumbnail)
                && isset(request()->additional)
                && self::checkIfProductsListAreMandatoryAndExists(request()->products_list)
            ) {
                $status = true;
            }
        }

        return $status;
    }

    public static function checkIfAttributesAreMandatoryAndExists($attributes): bool
    {
        if (request()->product_type !== 'bundle') {
            $data = array_map(
                static function ($data) {
                    $status = ! empty($data['attribute_value']);
                },
                $attributes
            );
            $status = ! in_array(false, $data);
        } else {
            $status = true;
        }

        return $status;
    }

    public static function checkIfProductsListAreMandatoryAndExists($products_list): bool
    {
        if (request()->product_type === 'bundle') {
            $status = ! empty($products_list);
        } else {
            $status = true;
        }

        return $status;
    }

    public static function checkIfCategoryIsRequired(): bool
    {
        return config('amplify.pim.categorization_required', false);
    }

    public static function checkIfUseClassifications(): bool
    {
        return config('amplify.pim.use_classifications', false);
    }

    public static function isRequiredFields(): bool
    {
        return config('amplify.pim.required_fields', false);
    }

    public static function getProductMandatoryFields(): array
    {
        return config('amplify.pim.mandatory_fields');
    }
}
