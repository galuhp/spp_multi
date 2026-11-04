<?php

namespace App\Support\ImporSiswa;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSiswaSheet implements FromArray, WithColumnFormatting, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ReferensiImporSiswa::KOLOM;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => '@',
            'B' => '@',
            'C' => '@',
            'P' => '@',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 20,
            'C' => 14,
            'D' => 28,
            'E' => 14,
            'F' => 20,
            'G' => 16,
            'H' => 14,
            'I' => 34,
            'J' => 16,
            'K' => 18,
            'L' => 18,
            'M' => 22,
            'N' => 22,
            'O' => 22,
            'P' => 18,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF1E3A8A'],
                ],
                'alignment' => ['vertical' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Template';
    }
}
