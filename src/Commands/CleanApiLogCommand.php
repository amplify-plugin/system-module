<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanApiLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:api-log-clean {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean Old API Logs from table with date interval';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $interval = (int) $this->option('days');

        if (is_numeric($interval)) {
            $dateString = now()->subDays($interval)->format('Y-m-d H:i:s');

            if (Schema::hasTable('api_logs')) {
                DB::table('api_logs')->where('created_at', '<', $dateString)->delete();

                $this->info("API Log upto ({$dateString}) has been deleted.");

                return CommandAlias::SUCCESS;
            }

            throw new \PDOException('`api_logs` table is missing from database');
        }

        throw new \InvalidArgumentException("The Days value must be a positive integer number given ({$interval}).");
    }
}
