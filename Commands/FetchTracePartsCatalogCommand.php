<?php

namespace Amplify\System\Commands;

use Amplify\System\Jobs\ImportProductsJob;
use Illuminate\Console\Command;

class FetchTracePartsCatalogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traceparts:catalog-fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger the process to fetch and process the TraceParts catalog XML files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ImportProductsJob::dispatch();
        $this->info('✅ CheckImportStatusJob has been dispatched successfully!');
    }
}
