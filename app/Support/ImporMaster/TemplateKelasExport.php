<?php

namespace App\Support\ImporMaster;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateKelasExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function headings(): array
    {
        return [
            'nama_kelas',
            'tingkat',
            'kode_jurusan',
            'tahun_ajaran',
            'wali_kelas',
        ];
    }

    public function array(): array
    {
        return [
            ['X RPL 1', '10', 'RPL', '2026/2027', 'siti.aminah'],
            ['X TKJ 1', '10', 'TKJ', '2026/2027', 'budi.santoso'],
            ['XI RPL 1', '11', 'RPL', '2026/2027', ''],
            ['XI TKJ 1', '11', 'TKJ', '2026/2027', ''],
            ['XII RPL 1', '12', 'RPL', '2026/2027', ''],
            ['XII TKJ 1', '12', 'TKJ', '2026/2027', ''],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 12,
            'C' => 18,
            'D' => 18,
            'E' => 25,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Template Kelas';
    }
}
