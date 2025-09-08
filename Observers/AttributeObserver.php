<?php

namespace Amplify\System\Observers;

use Amplify\System\Backend\Models\Attribute;

class AttributeObserver
{
    /**
     * Handle the Attribute "created" event.
     *
     * @return void
     */
    public function created(Attribute $attribute)
    {
        //
    }

    /**
     * Listen to the User updating event.
     *
     * @return void
     */
    public function updating($model)
    {
        if ($model->is_new === 1) {
            $model->is_new = 0;
        }

        if (! $model->is_updated) {
            $model->is_updated = 0;
        }
    }

    /**
     * Handle the Attribute "updated" event.
     *
     * @return void
     */
    public function updated(Attribute $attribute)
    {
        //
    }

    /**
     * Handle the Attribute "deleted" event.
     *
     * @return void
     */
    public function deleted(Attribute $attribute)
    {
        //
    }

    /**
     * Handle the Attribute "restored" event.
     *
     * @return void
     */
    public function restored(Attribute $attribute)
    {
        //
    }

    /**
     * Handle the Attribute "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Attribute $attribute)
    {
        //
    }
}
