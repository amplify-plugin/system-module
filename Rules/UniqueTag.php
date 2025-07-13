<?php

namespace Amplify\System\Rules;

use App\Models\Tag;
use Illuminate\Contracts\Validation\Rule;

class UniqueTag implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if (request()->isMethod('PUT')) {
            return ! Tag::where('id', '!=', request()->id)->where('name', $value)->exists();
        }

        return ! Tag::where('name', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be unique.';
    }
}
