<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DefragmentTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:sys-defragment-tables {--analyze=false} {--optimize=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Defragment tables to optimize database performance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $optimize = $this->option('optimize') !== 'false';
        $analyze = $this->option('analyze') !== 'false';

        if (!$optimize && !$analyze) {
            $this->error('Please specify at least one option: --optimize or --analyze');
            return self::SUCCESS;
        }

        $task = $optimize ? "OPTIMIZE" : "ANALYZE";

        try {
            $database = DB::getDatabaseName();

            $tables = DB::select(
                "SELECT `table_name` as `table`
                FROM information_schema.tables 
                WHERE table_schema = '{$database}' 
                AND table_rows >= 500");

            if (empty($tables)) {
                $this->error('No tables found with more than 500 rows. Optimization not required.');
                return self::SUCCESS;
            }

            $tables = array_column($tables, 'table');

            $pdo = DB::connection()->getPdo();

            $statement = $pdo->prepare("{$task} TABLE " . implode(", ", array_map(fn($t) => "`{$t}`", $tables)));

            $statement->execute();

            $this->info("Database {$task} tables successful @ " . date('r'));

            return self::SUCCESS;

        } catch (\PDOException $e) {

            report(new \Error("{$task} FAILED. Error: " . $e->getMessage(), 0, $e));

            return self::FAILURE;
        }
    }
}
