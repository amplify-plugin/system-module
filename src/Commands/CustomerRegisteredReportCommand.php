<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Event;
use Amplify\System\Exports\CustomerRegisteredExport;
use Amplify\System\Factories\NotificationFactory;
use Carbon\CarbonImmutable;
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
    protected $signature = 'amplify:customer-registered-report {--interval=monthly : The interval each report sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will generate list of all customer that registered between given interval.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $interval = $this->option('interval');

        abort_unless(in_array($interval, ["monthly", "daily", 'weekly', 'yearly', 'quarterly']), 500, 'Invalid value given. Correct values are: monthly, daily, weekly, yearly, quarterly');

        try {

            $endOfLastDate = now()->startOfDay()->subSecond();

            $std = now()->startOfDay()->subSecond();

            $startOfLastDate = match ($interval) {

                'daily' => $std->startOfDay(),
                'weekly' => $std->startOfWeek(),
                'quarterly' => $std->startOfQuarter(),
                'yearly' => $std->startOfYear(),
                default => $std->startOfMonth(),
            };

            $filename = ($interval == 'daily')
                ? "customers-of-{$startOfLastDate->format('Y-m-d')}.xlsx"
                : "customers-from-{$startOfLastDate->format('Y-m-d')}-to-{$endOfLastDate->format('Y-m-d')}.xlsx";

            Excel::store(
                new CustomerRegisteredExport($startOfLastDate, $endOfLastDate),
                $filename,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            NotificationFactory::call(Event::CUSTOMER_REGISTRATION_REPORT_GENERATED, [
                'interval' => ucfirst($interval),
                'start_date' => $startOfLastDate,
                'end_date' => $endOfLastDate,
                'filepath' => Storage::disk('public')->path($filename),
            ]);

            $this->info("Customer Registration From {$startOfLastDate} to {$endOfLastDate} generated on [" . Storage::disk('public')->path($filename) . '] completed.');

            return self::SUCCESS;

        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        return self::FAILURE;
    }
}
