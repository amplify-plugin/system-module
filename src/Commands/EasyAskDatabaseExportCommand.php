<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EasyAskDatabaseExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:easyask-export {tableList}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export search required tables then send to easyask';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            $tableList = explode(',', $this->argument('tableList'));

            $backupFileName = 'easyask-export-tables-' . date('Y-m-d') . '.sql';
            $zipFileName = 'easyask-export-tables-' . date('Y-m-d') . '.zip';
            if (!is_dir(storage_path('app/easyask'))) {
                mkdir(storage_path('app/easyask/'));
            }
            $sqlBackupPath = storage_path("app/easyask/{$backupFileName}");
            $zipFilePath = storage_path("app/easyask/{$zipFileName}");

            $connection = config('database.default');

            $databaseName = config("database.connections.{$connection}.database");
            $databaseUsername = config("database.connections.{$connection}.username");
            $databasePassword = config("database.connections.{$connection}.password");

            foreach ($tableList as $tableName) {
                if (Schema::hasTable($tableName)) {
                    exec("mysqldump -u {$databaseUsername} -p{$databasePassword} {$databaseName} {$tableName} >> {$sqlBackupPath}");
                }
            }

            $zip = new ZipArchive;
            $zip->open($zipFilePath, ZipArchive::CREATE);
            $zip->addFile($sqlBackupPath, $backupFileName);
            $zip->close();

            File::delete($sqlBackupPath);


            Storage::disk('sftp')->writeStream($zipFileName, fopen($zipFilePath, 'r'));

            File::delete($zipFilePath);

            $this->info("All selected tables have been exported and zipped to {$zipFileName}");

            return self::SUCCESS;

        } catch (\Exception $e) {

            report($e);

            Log::info($e->getMessage());

            return self::FAILURE;
        }
    }
}
