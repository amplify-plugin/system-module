<?php

namespace Amplify\System\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuickOrderImport implements ToCollection
{
    public function collection(Collection $collection): Collection
    {
        return $collection;
    }
}
