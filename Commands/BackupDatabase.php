<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {tableList}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // string to array conversion
        $tableList = explode(',', $this->argument('tableList'));

        $backupFileName = 'easyask-export-tables-'.date('Y-m-d').'.sql';
        $zipFileName = 'easyask-export-tables-'.date('Y-m-d').'.zip';
        if (! is_dir(storage_path('app/easyask'))) {
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

        // Create a zip file
        try {
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                $zip->addFile($sqlBackupPath, $backupFileName);
                $zip->close();

                File::delete($sqlBackupPath); // Delete created sql file
                $this->info("All selected tables have been exported and zipped to {$zipFileName}");
            } else {
                $this->error('Failed to create the table backup zip archive.');
            }
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }

        try {
            Storage::disk('sftp')->put('/'.$zipFileName, file_get_contents($zipFilePath));
            File::delete($zipFilePath); // Delete created zip file
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
