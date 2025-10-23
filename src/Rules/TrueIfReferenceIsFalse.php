<?php

namespace Amplify\System\Rules;

use Illuminate\Contracts\Validation\Rule;

class TrueIfReferenceIsFalse implements Rule
{
    protected array $columns;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(...$columns)
    {
        $this->columns = $columns;
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
        if ($value) {
            foreach ($this->columns as $column) {
                if (request()->get($column)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $fields = implode(',', $this->columns);

        return "One field can be active among [{$fields}] fields.";
    }
}
