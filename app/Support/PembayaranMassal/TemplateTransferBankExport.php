<?php

namespace App\Support\PembayaranMassal;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateTransferBankExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [
            ['0012345678', 250000, now()->format('Y-m-d'), 'TRF-001'],
            ['0098765432', 500000, now()->format('Y-m-d'), 'TRF-002'],
        ];
    }

    public function headings(): array
    {
        return ['NIS', 'JUMLAH', 'TANGGAL', 'REFERENSI'];
    }
}
