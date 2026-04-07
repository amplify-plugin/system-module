<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SyncPermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:permission-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Permissions with the route names';

    /**
     * Do not create permissions for these route names
     *
     * @var array
     */
    protected $ignoreByName = [
        // 'fm.content',
    ];

    /**
     * Do not create permissions for these route URIs
     *
     * @var array
     */
    protected $ignoreByUri = [
        // 'admin/force-reset-password/{user}',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {

            $this->aliases = config('permission.seeder.aliases', []);

            $this->insertOrIgnorePermission('backend', User::AUTH_GUARD);

            $roleHasPermissionTable = config('permission.table_names.role_has_permissions');

            $permissionTable = config('permission.table_names.permissions');

            if ($role = Role::whereName('Super Admin')
                ->whereGuardName(User::AUTH_GUARD)
                ->whereTeamId(User::SYSTEM_TEAM_ID)
                ->first()) {
                DB::statement("
    INSERT IGNORE INTO {$roleHasPermissionTable} (`permission_id`, `role_id`)
    SELECT `id`, {$role->id}
    FROM {$permissionTable} WHERE guard_name = '" . User::AUTH_GUARD . "'");
            }

            if (config('amplify.basic.is_permission_system_enabled', false) == true) {

                $this->insertOrIgnorePermission('frontend', Contact::AUTH_GUARD);
            }

            $this->call('permission:cache-reset');

            return self::SUCCESS;

        } catch (\Exception $exception) {
            report($exception);
            return self::FAILURE;
        }
    }

    private function insertOrIgnorePermission($key, $guard): void
    {
        $modules = config("permission.seeder.{$key}", []);

        $entries = [];

        foreach ($modules as $module => $aliases) {
            $entries = array_merge($entries, $this->mapPermissions($module, $aliases, $guard));
        }

        DB::table(config('permission.table_names.permissions', 'permissions'))->insertOrIgnore($entries);

        $this->info(ucfirst($key) . ' permissions synced @' . date('r'));

    }

    private function mapPermissions($module, ?string $aliases = null, string $guard = 'web'): array
    {
        $entries = [];

        if (is_int($module)) {
            $entries[] = $aliases;
        }

        $aliases = explode(',', $aliases);

        if (!empty($aliases)) {
            foreach ($aliases as $alias) {
                $permission = (isset($this->aliases[$alias])) ? $this->aliases[$alias] : $alias;
                $entries[] = "{$module}.{$permission}";
            }
        }

        $now = now()->format('Y-m-d H:i:s');

        return array_map(
            fn($p) => ['name' => $p, 'guard_name' => $guard, 'created_at' => $now, 'updated_at' => $now],
            $entries
        );
    }
}
