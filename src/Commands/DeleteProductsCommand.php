<?php

namespace Amplify\System\Commands;

use Amplify\System\Jobs\DeleteProductsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the deleted-products.csv file to soft delete products.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $uploadsDirectory = config('amplify.icu.uploads_directory');
        $archivesDirectory = config('amplify.icu.archives_directory');
        $filePath = $uploadsDirectory.'/deleted-products.csv';

        if (! file_exists($filePath)) {
            $this->info('No file found named deleted-products.csv in the directory.');
            Log::channel('dds')->info('No file found named deleted-products.csv in the directory.');

            return;
        }

        // Read file lines
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($lines)) {
            $this->info('The file deleted-products.csv is empty.');
            Log::channel('dds')->info('The file deleted-products.csv is empty.');

            return;
        }

        // Validate header for distributor_product_id
        $header = str_getcsv($lines[0]);
        if (! in_array('distributor_product_id', $header)) {
            $this->info('The file deleted-products.csv does not contain the "distributor_product_id" column in the header.');
            Log::channel('dds')->info('The file deleted-products.csv does not contain the "distributor_product_id" column in the header.');

            return;
        }

        // Get the index of the distributor_product_id column
        $distributorProductIdIndex = array_search('distributor_product_id', $header);

        // Skip the header line
        $dataLines = array_slice($lines, 1);

        if (empty($dataLines)) {
            $this->info('No product data found in deleted-products.csv after skipping the header.');
            Log::channel('dds')->info('No product data found in deleted-products.csv after skipping the header.');

            return;
        }

        $chunkSize = 100;
        $chunks = array_chunk($dataLines, $chunkSize);

        foreach ($chunks as $chunk) {
            DeleteProductsJob::dispatch($chunk, $distributorProductIdIndex);
        }

        $this->archiveFile($filePath, $archivesDirectory);
    }

    /**
     * Archive the processed file to a date-stamped directory.
     */
    protected function archiveFile(string $filePath, string $archivesDirectory): void
    {
        $archivesDirectoryPath = $archivesDirectory.'/'.date('Y-m-d-H-i').'-deleted-products';
        if (! is_dir($archivesDirectoryPath) && ! mkdir($archivesDirectoryPath, 0775, true)) {
            $this->error("Unable to create archive directory: $archivesDirectoryPath.");
            Log::channel('dds')->error("Unable to create archive directory: $archivesDirectoryPath.");

            return;
        }

        $archivedFilePath = $archivesDirectoryPath.'/'.date('Y-m-d-H-i').'-deleted-products.csv';
        if (! rename($filePath, $archivedFilePath)) {
            $this->error("Failed to move file $filePath to $archivedFilePath.");
            Log::channel('dds')->error("Failed to move file $filePath to $archivedFilePath.");
        } else {
            $this->info("File $filePath successfully archived to $archivedFilePath.");
            Log::channel('dds')->info("File $filePath successfully archived to $archivedFilePath.");
        }
    }
}
