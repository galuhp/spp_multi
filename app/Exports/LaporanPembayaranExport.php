<?php

namespace App\Exports;

use App\Models\Pembayaran;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPembayaranExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    /**
     * @param  Collection<int, Pembayaran>  $pembayarans
     */
    public function __construct(
        protected Collection $pembayarans,
    ) {}

    public function collection(): Collection
    {
        return $this->pembayarans;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal Bayar',
            'Kode Pembayaran',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Kategori',
            'Periode / Keterangan',
            'Metode Pembayaran',
            'Nominal Bayar (Rp)',
            'Petugas',
        ];
    }

    /**
     * @param  Pembayaran  $row
     * @return array<int, int|string>
     */
    public function map(mixed $row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->tgl_bayar?->format('Y-m-d') ?? '-',
            $row->kode_pembayaran ?? '-',
            $row->siswa?->nisn ?? '-',
            $row->siswa?->nama ?? '-',
            $row->siswa?->kelas?->nama_kelas ?? '-',
            $row->spp?->kategori ?? '-',
            $row->periodeLabel() ?? ($row->keterangan ?? '-'),
            strtoupper((string) ($row->metode_pembayaran ?? '-')),
            (int) $row->jumlah_bayar,
            $row->user?->name ?? 'Sistem',
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
