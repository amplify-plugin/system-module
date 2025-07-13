<?php

namespace Amplify\System\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class SizeInValidationRule implements ValidationRule
{
    private $options;

    public function __construct(...$options)
    {
        $this->options = $options;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array(strlen($value), $this->options)) {
            $fail('The '.Str::replace('_', ' ', $attribute).' is must be of length 8, 12, 13 or 14.');
        }
    }
}
