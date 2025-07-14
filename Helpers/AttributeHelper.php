<?php

namespace Amplify\System\Helpers;

class AttributeHelper
{
    /**
     * @return string
     */
    public static function getLocaleValue(string $attribute_value)
    {
        $default = config('app.locale');
        $locale = request('locale') ?? $default;
        $attribute_value_json = json_decode($attribute_value, true);

        return $attribute_value_json && $attribute_value != $attribute_value_json
            ? ($attribute_value_json[$locale] ?? current($attribute_value_json))
            : $attribute_value;
    }
}
