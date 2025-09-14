<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpgradeIssueFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:upgrade-issue-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->components->task("Fixing Placeholder Image Issue", function () {
            DB::table('system_configurations')->where('name', '=', 'frontend')
                ->where('option', '=', 'fallback_image_path')
                ->delete();
        });

        $this->components->task("Fixing Permission Model Namespace Issue", function () {
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->update(['model_type' => 'Amplify\\System\\Backend\\Models\\User']);

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\Contact')
                ->update(['model_type' => 'Amplify\\System\\Backend\\Models\\Contact']);
        });

        if ($this->components->confirm("Did you Setup the Storage Configuration in .Env", true)) {
            $this->components->task("Removing Storage Configuration from DB", function () {
                DB::table('system_configurations')->where('name', '=', 'storage')
                    ->delete();
            });
        } else {
            $this->components->info("Please Configure the storage configuration in .env and remove it from `system_configurations` table.");
        }

        if ($this->components->confirm("Did you Setup the Email Configuration in .Env", true)) {
            $this->components->task("Removing Email Configuration from DB", function () {
                DB::table('system_configurations')
                    ->where('name', '=', 'email')
                    ->delete();
            });
        } else {
            $this->components->info("Please Configure the storage configuration in .env and remove it from `system_configurations` table.");
        }

        $this->components->task("Removing Goggle, Icecat,Incremental Update,Messages,prop65,punchout,Report Configuration from DB", function () {
            DB::table('system_configurations')->whereIn('name',  ['google', 'icecat', 'icu', 'messages', 'prop65', 'punchout', 'report'])
                ->delete();
        });

    }
}
