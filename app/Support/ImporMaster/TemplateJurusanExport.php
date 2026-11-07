<?php

namespace App\Support\ImporMaster;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateJurusanExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function headings(): array
    {
        return [
            'kode',
            'nama',
            'deskripsi',
        ];
    }

    public function array(): array
    {
        return [
            ['RPL', 'Rekayasa Perangkat Lunak', 'Pengembangan perangkat lunak, sistem informasi, dan aplikasi web/mobile'],
            ['TKJ', 'Teknik Komputer dan Jaringan', 'Perakitan komputer, instalasi jaringan, sistem administrasi server'],
            ['AKL', 'Akuntansi dan Keuangan Lembaga', 'Pengelolaan pembukuan, laporan keuangan, dan akuntansi lembaga'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 60,
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
        return 'Template Jurusan';
    }
}
