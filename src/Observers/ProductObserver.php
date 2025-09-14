<?php

namespace Amplify\System\Observers;

class ProductObserver
{
    /**
     * Handle the "created" event.
     *
     * @return void
     */
    public function created($model)
    {
        //
    }

    /**
     * Handle the "updated" event.
     *
     * @return void
     */
    public function updated($model)
    {
        //
    }

    /**
     * Listen to the updating event.
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
     * Listen to the creating event.
     *
     * @return void
     */
    public function creating($model)
    {
        //
    }

    /**
     * Handle the "deleted" event.
     *
     * @return void
     */
    public function deleted($model)
    {
        //
    }

    /**
     * Handle the "restored" event.
     *
     * @return void
     */
    public function restored($model)
    {
        //
    }

    /**
     * Handle the "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted($model)
    {
        //
    }
}
