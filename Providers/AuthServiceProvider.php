<?php

namespace Amplify\System\Providers;

use Amplify\System\Backend\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            // Allow Backend User Model Only Permission
            if ($user instanceof User) {
                setPermissionsTeamId(0);
                if ($user->hasRole('Super Admin')) {
                    return true;
                }

                return false;
            }

            // check frontend role feature is enabled or not
            if (! config('amplify.basic.is_permission_system_enabled')) {
                return true;
            }

            return null;
        });
    }
}
