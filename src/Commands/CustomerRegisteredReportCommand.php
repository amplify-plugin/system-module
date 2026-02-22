<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Event;
use Amplify\System\Exports\CustomerRegisteredExport;
use Amplify\System\Factories\NotificationFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CustomerRegisteredReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:customer-registered-report {--days=30 : The interval each report sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will generate list of all customer that registered between given interval.';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {

        try {
            $interval = intval($this->option('days') ?? 30);

            $start = now()->subDays($interval)->format('Y-m-d');

            $end = now()->format('Y-m-d');

            $filename = "customers-from-{$start}-to-{$end}.xlsx";

            Excel::store(
                new CustomerRegisteredExport($interval),
                $filename,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            NotificationFactory::call(Event::CUSTOMER_REGISTRATION_REPORT_GENERATED, [
                'interval' => $interval,
                'filepath' => Storage::disk('public')->path($filename)
            ]);

            $this->info("Customer Registration From {$start} to {$end} generated on [". Storage::disk('public')->path($filename). "] completed.");

            return self::SUCCESS;

        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
        return self::FAILURE;
    }
}
