<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;

class BackupRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:create-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a scheduled backup and remove backups from disk';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $tempDisk;

    protected $tempDirectory = 'backup-temp';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $destDisk;

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $startAt = now();

            $this->verifyConditions();

            $this->dumpDatabases();

            $this->moveToDestination();

            $this->info("Database backup finished in " . str_replace([' after'], '', now()->diffForHumans($startAt)) . '.');
        } catch (\Throwable $e) {

            $this->info($e->getMessage());

            report($e);
        }
    }

    /**
     * @throws \Exception
     */
    private function verifyConditions(): void
    {
        $this->validateTempDirectory();

        $this->validateDestDirectory();

        $this->validateRequiredLibrary();

        $pdo = DB::connection()->getPdo();
    }

    /**
     * @throws \Exception
     */
    private function validateTempDirectory(): void
    {
        $this->tempDisk = Storage::disk('local');

        if ($this->tempDisk->exists($this->tempDirectory)) {

            $this->tempDisk->deleteDirectory($this->tempDirectory);
        }

        mkdir($this->tempDisk->path($this->tempDirectory), 0777, true);

        if ($this->tempDisk->put("{$this->tempDirectory}/.gitignore", "*\n!.gitignore\n") === false) {
            throw new \Exception("Failed to create backup temporary directory.");
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function validateDestDirectory(): void
    {
        $this->destDisk = Storage::disk('backups');

        if (!$this->destDisk->exists('/')) {
            throw new InvalidArgumentException("Failed to access backup destination directory.");
        }

        if ($this->destDisk->put('backup.test', '') === false) {
            throw new FileNotFoundException("Backup destination directory is not writable.");
        } else {
            $this->destDisk->delete('backup.test');
        }
    }

    /**
     * @throws \ErrorException
     */
    private function validateRequiredLibrary(): void
    {
        $connection = DB::getDefaultConnection();

        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'mysql') {
            throw new InvalidArgumentException(ucfirst($driver) . " database driver is not supported.");
        }

        if (!function_exists('exec')) {
            throw new InvalidArgumentException("Missing exec() function.");
        }

        exec('mysqldump --version', $output, $status);

        if ($status !== 0) {
            throw new \ErrorException("`mysqldump` command is not available.");
        }

        exec('which zip', $output, $status);

        if ($status !== 0) {
            throw new \ErrorException("`zip` command is not available.");
        }
    }

    /**
     * @throws \ErrorException
     */
    private function dumpDatabases(): void
    {
        $connections[] = config('database.default');

        if (config('amplify.pim.pim_db_enabled') === true) {
            $connections[] = 'pim_db';
        }

        foreach ($connections as $connection) {
            $config = config("database.connections.{$connection}", []);

            $database = $config['database'] ?? null;
            $username = escapeshellarg($config['username'] ?? null);
            $password = $config['password'] ?? null;
            $host = escapeshellarg($config['host'] ?? null);
            $port = escapeshellarg($config['port'] ?? null);

            $filename = Str::kebab($database) . "_" . now()->format('Y-m-d_H-i-s');

            $sqlFile = escapeshellarg(
                $this->tempDisk->path(
                    $this->tempDirectory .
                    DIRECTORY_SEPARATOR .
                    $filename .
                    '.sql')
            );

            $command = [
                'mysqldump',
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
                "--single-transaction",
                "--quick",
                "--skip-lock-tables",
                "--no-tablespaces",
                "--set-gtid-purged=OFF",
                "--column-statistics=0",
                "--result-file={$sqlFile}",
                escapeshellarg($database),
            ];

            if (!empty($password)) {
                $command[] = "--password=" . escapeshellarg($password);
            }

            $this->info("Dumping {$database} database...");

            exec(implode(' ', $command), $output, $statusCode);

            if ($statusCode !== 0) {
                throw new \ErrorException("Dumping `{$database}` failed with status code `{$statusCode}`.");
            }
        }
    }

    /**
     * @throws \ErrorException
     */
    private function compressBackupFiles(): string
    {

        $filename = 'amplify-db-backup-' . date('Y-m-d-H-i-s') . '.zip';

        $fullPath = $this->tempDisk->path($this->tempDirectory . DIRECTORY_SEPARATOR . $filename);

        $files = glob($this->tempDisk->path($this->tempDirectory . DIRECTORY_SEPARATOR . '*.sql'));

        $command = [
            "zip",
            "-j",
            "-q",
            escapeshellarg($fullPath),
            implode(" ", array_map('escapeshellarg', $files))
        ];

        exec(implode(' ', $command), $output, $status);

        if ($status !== 0) {
            throw new \ErrorException("Failed to compress backup files");
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return $fullPath;
    }


    /**
     * @throws \Exception
     */
    private function moveToDestination(): void
    {
        $zipFilePath = $this->compressBackupFiles();

        $driver = config("filesystems.disks.backups.driver");

        if ($driver == 'local') {
            if (!$this->destDisk->move($zipFilePath, basename($zipFilePath))) {
                throw new \Exception("Failed to move backup directory.");
            }
        }

        if ($driver == 's3') {
            if (!$this->destDisk->writeStream(basename($zipFilePath), fopen($zipFilePath, 'r'))
                || !$this->tempDisk->delete($zipFilePath)) {
                throw new \Exception("Failed to move backup directory.");
            }
        }
    }
}
