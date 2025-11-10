<?php

namespace Amplify\System\Commands;

use Amplify\System\Jobs\IncrementalCatalogUpdateJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IncrementalCatalogUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:incremental-catalog-update {--date=*} {--delay=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Amplify DDS Data import process';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $uploadsDirectory = config('amplify.icu.uploads_directory');
        $archivesDirectory = config('amplify.icu.archives_directory');
        $filenamePrefix = config('amplify.icu.filename_prefix');

        $delay = $this->option('delay');
        $dates = $this->option('date');

        $files = [];
        if ($dates) {
            $importPath = $uploadsDirectory.'/files';

            if (! is_dir($importPath)) {
                Log::channel('dds')->error("Import directory ($importPath) does not exist.");

                return;
            }

            $allFiles = glob("$importPath/$filenamePrefix*.json");

            foreach ($allFiles as $filePath) {
                $fileDate = date('Y-m-d', filemtime($filePath));

                if (in_array($fileDate, $dates)) {
                    $files[] = $filePath;
                }
            }

            if (empty($files)) {
                Log::channel('dds')->info('No files found for the specified dates.');
            } else {
                Log::channel('dds')->info('Found files: '.implode(', ', $files));
            }
        } else {
            $importPath = $uploadsDirectory.'/incremental/files';

            if (! is_dir($importPath)) {
                Log::channel('dds')->error("'Import directory ($importPath) does not exist.");

                return;
            }

            $files = glob("$importPath/$filenamePrefix*.json");

            if (empty($files)) {
                Log::channel('dds')->info("No files found with prefix $filenamePrefix in $importPath.");

                return;
            }
        }

        foreach ($files as $file) {
            $this->processFile($file, $archivesDirectory, $delay);
        }
    }

    /**
     * Process a single file in chunks and dispatch jobs.
     */
    protected function processFile(string $file, string $archivesDirectory, int $delay): void
    {
        try {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $chunkSize = 5; // Adjust as per your needs
            $chunks = array_chunk($lines, $chunkSize);

            foreach ($chunks as $chunk) {
                IncrementalCatalogUpdateJob::dispatch($chunk)->delay($delay);
            }

            $this->archiveFile($file, $archivesDirectory);
        } catch (Exception $e) {
            Log::channel('dds')->error("Error processing file $file: ", ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Archive the processed file to a date-stamped directory.
     */
    protected function archiveFile(string $file, string $archivesDirectory): void
    {
        $archivesDirectoryPath = $archivesDirectory.'/'.date('Y-m-d-H-i');
        if (! is_dir($archivesDirectoryPath) && ! mkdir($archivesDirectoryPath, 0775, true)) {
            Log::channel('dds')->error("Unable to create archive directory: $archivesDirectoryPath.");

            return;
        }

        $archivedFilePath = $archivesDirectoryPath.'/'.basename($file);
        if (! rename($file, $archivedFilePath)) {
            Log::channel('dds')->error("Failed to move file $file to $archivedFilePath.");
        } else {
            Log::channel('dds')->info("File $file successfully archived to $archivedFilePath.");
        }
    }
}
