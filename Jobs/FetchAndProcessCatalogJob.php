<?php

namespace Amplify\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FetchAndProcessCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('🚀 Starting Puppeteer script to fetch catalog files...');

            // Run Node.js script using Puppeteer
            $process = new Process(['node', base_path('scripts/downloadCatalog.js')]);
            $process->setTimeout(600); // Set timeout to 10 minutes
            $process->run();

            // Check for errors
            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            Log::info('✅ Puppeteer script executed successfully.');

        } catch (\Exception $e) {
            Log::error('🚨 Exception in FetchAndProcessCatalogJob: '.$e->getMessage());
        }
    }
}
