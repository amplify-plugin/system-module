<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:clean {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean Old Audit Logs from table with date interval';

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

            if (Schema::hasTable('audits')) {
                DB::table('audits')->where('created_at', '<', $dateString)->delete();

                $this->info("Audit Log upto ({$dateString}) has been deleted.");

                return CommandAlias::SUCCESS;
            }

            throw new \PDOException('`audits` table is missing from database');
        }

        throw new \InvalidArgumentException("The Days value must be a positive integer number given ({$interval}).");
    }
}
