<?php

namespace Amplify\System\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class ImportJobExport implements FromCollection
{
    private Collection $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function collection(): Collection
    {
        return $this->collection;
    }
}
