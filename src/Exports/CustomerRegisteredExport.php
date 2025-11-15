<?php

namespace Amplify\System\Exports;

use Amplify\System\Backend\Models\Customer;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustomerRegisteredExport implements FromView, ShouldAutoSize
{
    use Exportable;

    public function __construct(public int $interval = 30)
    {
    }

    public function view(): View
    {
        $customers = Customer::query()->whereDate('customers.created_at', '>=', now()->subDays($this->interval))
            ->whereDate('customers.created_at', '<=', now())
            ->get();

        return \view('system::report.customer', [
            'startDate' => now()->subDays($this->interval),
            'endDate' => now(),
            'customers' => $customers
        ]);
    }
}
