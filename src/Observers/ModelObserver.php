<?php

namespace Amplify\System\Observers;

use Illuminate\Support\Facades\Storage;

class ModelObserver
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
     * Handle the "deleted" event.
     *
     * @return void
     */
    public function deleted($model)
    {
        Storage::disk(config('backpack.base.root_disk_name', 'public_uploads'))->delete('public/'.$model->image);
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
