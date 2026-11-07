<?php

namespace App\Support\ImporMaster;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateTarifExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function headings(): array
    {
        return [
            'kategori',
            'nama',
            'tipe',
            'tahun_ajaran',
            'tingkat',
            'nominal',
            'keterangan',
        ];
    }

    public function array(): array
    {
        return [
            ['SPP', 'SPP Bulanan', 'bulanan', '2026/2027', '', 100000, 'SPP bulanan (kosongkan tingkat jika berlaku untuk semua tingkat)'],
            ['Biaya Ujian', 'PTS Ganjil', 'sekali_bayar', '2026/2027', '10', 250000, 'Tarif PTS Ganjil Tingkat 10 TA 2026/2027'],
            ['Biaya Ujian', 'PTS Genap', 'sekali_bayar', '2026/2027', '10', 250000, 'Tarif PTS Genap Tingkat 10 TA 2026/2027'],
            ['Uang Seragam', 'Kaos Olah Raga', 'sekali_bayar', '2026/2027', '10', 130000, 'Seragam Olahraga Kelas X'],
            ['Biaya Praktik', 'Uji Kompetensi Keahlian', 'sekali_bayar', '2026/2027', '12', 300000, 'Ujian Praktik UKK Tingkat 12'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 28,
            'C' => 16,
            'D' => 18,
            'E' => 14,
            'F' => 16,
            'G' => 45,
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
        return 'Template Tarif SPP';
    }
}
