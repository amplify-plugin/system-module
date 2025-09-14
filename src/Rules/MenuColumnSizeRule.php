<?php

namespace Amplify\System\Rules;

use Amplify\System\Cms\Models\MegaMenu;
use Illuminate\Contracts\Validation\Rule;

class MenuColumnSizeRule implements Rule
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

    protected $menuFreeColumnCount;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->menuFreeColumnCount = MegaMenu::menuFreeColumn(request()->menu_id, request()->route('id'));

        return $this->menuFreeColumnCount > 0 && $value <= $this->menuFreeColumnCount;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "You can allocate Maximum {$this->menuFreeColumnCount} ".($this->menuFreeColumnCount > 1 ? 'columns' : 'column');
    }
}
