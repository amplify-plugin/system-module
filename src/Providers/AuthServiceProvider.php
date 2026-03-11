<?php

namespace Amplify\System\Providers;

use Amplify\System\Backend\Models\Contact;
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

            if ($user instanceof User) {
                // Allow Everything
                if ($user->is_admin == 1) {
                    return true;
                }

                //Continue Check
                setPermissionsTeamId(User::SYSTEM_TEAM_ID);
                return null;
            }

            if ($user instanceof Contact) {
                // Allow Everything
                if (!config('amplify.basic.is_permission_system_enabled')) {
                    return true;
                }
                //Continue Check
                setPermissionsTeamId($user->customer()->id);
                return null;
            }

            return null;
        });
    }
}
