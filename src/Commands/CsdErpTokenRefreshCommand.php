<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CsdErpTokenRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:csd-erp-token-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This a schedule command to update token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('amplify.erp.default', 'default') == 'csd-erp') {
            try {
                \Amplify\ErpApi\Facades\ErpApi::refreshToken(true);

                Log::debug("CSD-ERP token refreshed successful");

                return self::SUCCESS;
            } catch (\Exception $e) {
                Log::error($e);
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }
}
