<?php

namespace Amplify\System\Rules;

use Illuminate\Contracts\Validation\Rule;

class AtLeastOneTrue implements Rule
{
    private $attributes = [];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->attributes[] = $attribute;
        foreach ($this->attributes as $attr) {
            if (request()->{$attr}) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'At least one of this checkbox is required ';
    }
}
