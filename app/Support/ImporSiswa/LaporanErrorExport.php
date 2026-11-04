<?php

namespace App\Support\ImporSiswa;

use App\Enums\StatusBarisImpor;
use App\Models\ImportSiswaStaging;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanErrorExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private string $batchId) {}

    /**
     * @return Collection<int, array<int, ?string>>
     */
    public function collection(): Collection
    {
        return ImportSiswaStaging::query()
            ->where('batch_id', $this->batchId)
            ->where('status', StatusBarisImpor::Error->value)
            ->orderBy('baris')
            ->get()
            ->map(fn (ImportSiswaStaging $baris): array => [
                $baris->nisn,
                $baris->nik,
                $baris->nis,
                $baris->nama_lengkap,
                $baris->jenis_kelamin,
                $baris->tempat_lahir,
                $baris->tanggal_lahir,
                $baris->agama,
                $baris->alamat,
                $baris->tanggal_masuk,
                $baris->kode_kompetensi,
                $baris->kelas,
                $baris->nama_ayah,
                $baris->nama_ibu,
                $baris->nama_wali,
                $baris->hp_wali,
                $baris->alasan,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [...ReferensiImporSiswa::KOLOM, 'alasan'];
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
            'Q' => 60,
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
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Error';
    }
}
