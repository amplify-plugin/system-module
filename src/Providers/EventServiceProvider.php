<?php

namespace Amplify\System\Providers;

use Amplify\System\Listeners\QueueAfterListener;
use Amplify\System\Listeners\QueueBeforeListener;
use Amplify\System\Listeners\QueueFailedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
        protected $listen = [
            JobProcessing::class => [
                QueueBeforeListener::class,
            ],
            JobProcessed::class => [
                QueueAfterListener::class,
            ],
            JobFailed::class => [
                QueueFailedListener::class,
            ],
        ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
