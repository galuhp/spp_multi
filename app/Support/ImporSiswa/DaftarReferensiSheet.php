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
                $kelas->tahunAjaran?->nama ?? 'Lintas Tahun (Master)',
            ];
        }

        $baris[] = ['', '', '', ''];
        $baris[] = ['TARIF SPP SESUAI TINGKAT (RUJUKAN OTOMATIS)', '', '', ''];
        $this->barisJudul[] = count($baris);
        $baris[] = ['Tingkat', 'Tahun Ajaran', 'Nominal SPP', 'Keterangan'];

        $tarifs = \App\Models\Spp::query()
            ->where('kategori', \App\Models\Spp::KATEGORI_SPP)
            ->where('tipe', \App\Models\Spp::TIPE_BULANAN)
            ->orderBy('tahun_ajaran')
            ->orderBy('tingkat')
            ->get();

        foreach ($tarifs as $spp) {
            $baris[] = [
                $spp->tingkat ? 'Tingkat '.\App\Models\Spp::labelTingkat($spp->tingkat) : 'Semua Tingkat',
                (string) $spp->tahun_ajaran,
                'Rp '.number_format((float) $spp->nominal, 0, ',', '.').' / bulan',
                'Tarif baku tingkat '.$spp->tingkat,
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
