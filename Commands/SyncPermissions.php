<?php

namespace Amplify\System\Commands;

use Backpack\PermissionManager\app\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

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
        setPermissionsTeamId(0);

        if (config('permission-seeder.truncate_tables')) {
            Schema::disableForeignKeyConstraints();
            DB::table('model_has_permissions')->truncate();
            DB::table('role_has_permissions')->truncate();
            Permission::truncate();
            Schema::enableForeignKeyConstraints();
        }

        $permissionConfWithGuard = config('permission-seeder.permissions');
        $permissionAlias = config('permission-seeder.alias');

        foreach ($permissionConfWithGuard as $guard => $permissionConf) {

            foreach ($permissionConf as $route => $permissions) {

                $permissions = explode(',', $permissions);
                $permissionLength = count($permissions) - 1;

                for ($i = 0; $i <= $permissionLength; $i++) {

                    $permission_name = $route.'.'.(isset($permissionAlias[$permissions[$i]]) ? $permissionAlias[$permissions[$i]] : $permissions[$i]);

                    $permission = Permission::updateOrCreate([
                        'name' => $permission_name,
                        'guard_name' => $guard,
                    ]);

                    if ($guard == 'web') {
                        if ($role = Role::findByName('Super Admin')) {
                            $role->givePermissionTo($permission->name);
                        }
                    }

                }
            }

        }

        $this->info('Permissions synchronization with the route names was successful!');

        $roleConf = config('permission-seeder.roles');

        foreach ($roleConf as $name => $conf) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => $conf['guard'],
                'team_id' => $conf['guard'] == 'web' ? 0 : null,
            ]);

            $role->givePermissionTo($conf['permissions']);
        }

        $this->info('Roles synchronization with the permission was successful!');

        return 0;
    }
}
