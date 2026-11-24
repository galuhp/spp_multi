<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanTunggakanExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    /**
     * @param  Collection<int, array<string, mixed>>  $siswaRows
     */
    public function __construct(
        protected Collection $siswaRows,
    ) {}

    public function collection(): Collection
    {
        return $this->siswaRows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Tarif SPP/Bln (Rp)',
            'Tunggakan SPP (Rp)',
            'Tunggakan Non-SPP (Rp)',
            'Total Tunggakan (Rp)',
            'Bulan Tertunggak',
            'Pos Non-SPP Tertunggak',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, int|string>
     */
    public function map(mixed $row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row['siswa']?->nisn ?? '-',
            $row['siswa']?->nama ?? '-',
            $row['kelas']?->nama_kelas ?? '-',
            (int) ($row['nominal'] ?? 0),
            (int) ($row['tunggakan_spp'] ?? 0),
            (int) ($row['tunggakan_non_spp'] ?? 0),
            (int) ($row['tunggakan'] ?? 0),
            empty($row['bulan_tertunggak']) ? '-' : implode(', ', $row['bulan_tertunggak']),
            empty($row['pos_non_spp_tertunggak']) ? '-' : implode(', ', $row['pos_non_spp_tertunggak']),
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
