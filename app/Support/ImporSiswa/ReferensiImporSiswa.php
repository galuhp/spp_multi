<?php

namespace App\Support\ImporSiswa;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Collection;

class ReferensiImporSiswa
{
    /**
     * @var list<string>
     */
    public const AGAMA = [
        'Islam',
        'Kristen',
        'Katolik',
        'Hindu',
        'Buddha',
        'Konghucu',
        'Lainnya',
    ];

    /**
     * @var list<string>
     */
    public const KOLOM = [
        'NISN',
        'NIK',
        'NIS',
        'Nama Lengkap',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Agama',
        'Alamat',
        'Tanggal Masuk',
        'Kode Kompetensi',
        'Kelas',
        'Nama Ayah',
        'Nama Ibu',
        'Nama Wali',
        'HP Wali',
    ];

    /**
     * @return Collection<int, Jurusan>
     */
    public static function jurusan(): Collection
    {
        return Jurusan::query()->orderBy('kode')->get();
    }

    /**
     * @return Collection<int, Kelas>
     */
    public static function kelas(): Collection
    {
        return Kelas::query()
            ->with(['jurusan', 'tahunAjaran'])
            ->orderBy('tahun_ajaran_id')
            ->orderBy('nama_kelas')
            ->get();
    }
}
