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

    public function __construct(public $startOfLastDay, public $endOfLastDay)
    {
    }

    public function view(): View
    {
        $customers = Customer::query()
            ->whereDate('customers.created_at', '>=', $this->startOfLastDay)
            ->whereDate('customers.created_at', '<=', $this->endOfLastDay)
            ->get();

        return \view('system::report.customer', [
            'startDate' => $this->startOfLastDay,
            'endDate' => $this->endOfLastDay,
            'customers' => $customers,
        ]);
    }
}
