<?php

namespace App\Support\ImporSiswa;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DaftarReferensiSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $baris = [];

    /**
     * @var list<int>
     */
    private array $barisJudul = [];

    public function __construct()
    {
        $this->baris = $this->susunBaris();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return $this->baris;
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 26,
            'B' => 44,
            'C' => 30,
            'D' => 20,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->barisJudul as $baris) {
            $styles[$baris] = ['font' => ['bold' => true, 'color' => ['argb' => 'FF1E3A8A']]];
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Daftar Referensi';
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function susunBaris(): array
    {
        $baris = [];
        $baris[] = ['KODE KOMPETENSI', '', '', ''];
        $this->barisJudul[] = count($baris);
        $baris[] = ['Kode', 'Nama Jurusan', '', ''];

        foreach (ReferensiImporSiswa::jurusan() as $jurusan) {
            $baris[] = [$jurusan->kode, $jurusan->nama, '', ''];
        }

        $baris[] = ['', '', '', ''];
        $baris[] = ['KELAS PADA TAHUN AJARAN', '', '', ''];
        $this->barisJudul[] = count($baris);
        $baris[] = ['Kelas', 'Kompetensi', 'Tingkat', 'Tahun Ajaran'];

        foreach (ReferensiImporSiswa::kelas() as $kelas) {
            $baris[] = [
                $kelas->nama_kelas,
                $kelas->jurusan?->nama ?? '-',
                (string) $kelas->tingkat,
                $kelas->tahunAjaran?->nama ?? '-',
            ];
        }

        $baris[] = ['', '', '', ''];
        $baris[] = ['AGAMA', '', '', ''];
        $this->barisJudul[] = count($baris);
        $baris[] = ['Agama', '', '', ''];

        foreach (ReferensiImporSiswa::AGAMA as $agama) {
            $baris[] = [$agama, '', '', ''];
        }

        return $baris;
    }
}
