<?php

namespace App\Support\PembayaranMassal;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransferBankImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        // Rows are processed in chunks by the caller.
    }
}
