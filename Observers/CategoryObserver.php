<?php

namespace Amplify\System\Observers;

use App\Models\Category;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     *
     * @return void
     */
    public function created(Category $category)
    {
        //
    }

    /**
     * Handle the Category "updated" event.
     *
     * @return void
     */
    public function updated(Category $category)
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
     * Handle the Category "deleted" event.
     *
     * @return void
     */
    public function deleted(Category $category)
    {
        //
    }

    /**
     * Handle the Category "restored" event.
     *
     * @return void
     */
    public function restored(Category $category)
    {
        //
    }

    /**
     * Handle the Category "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Category $category)
    {
        //
    }
}
