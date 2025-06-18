<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class SkippedRowsExport implements FromCollection
{
    protected $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }
}

