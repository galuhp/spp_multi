<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapPotensiPenerimaanExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  Collection<int, array<string, mixed>>  $baris
     */
    public function __construct(
        protected Collection $baris,
    ) {}

    public function collection(): Collection
    {
        return $this->baris;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Kelompok',
            'Jumlah Siswa',
            'Siswa Menunggak',
            'Tagihan SPP (Rp)',
            'Dibayar SPP (Rp)',
            'Tunggakan SPP (Rp)',
            'Tagihan Non-SPP (Rp)',
            'Dibayar Non-SPP (Rp)',
            'Tunggakan Non-SPP (Rp)',
            'Total Tagihan (Rp)',
            'Total Tunggakan (Rp)',
            'Capaian (%)',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, float|int|string>
     */
    public function map(mixed $row): array
    {
        return [
            (string) ($row['label'] ?? '-'),
            (int) ($row['jumlah_siswa'] ?? 0),
            (int) ($row['jumlah_menunggak'] ?? 0),
            (int) ($row['tagihan_spp'] ?? 0),
            (int) ($row['dibayar_spp'] ?? 0),
            (int) ($row['tunggakan_spp'] ?? 0),
            (int) ($row['tagihan_non_spp'] ?? 0),
            (int) ($row['dibayar_non_spp'] ?? 0),
            (int) ($row['tunggakan_non_spp'] ?? 0),
            (int) ($row['tagihan'] ?? 0),
            (int) ($row['tunggakan'] ?? 0),
            round((float) ($row['persentase'] ?? 0), 1),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
