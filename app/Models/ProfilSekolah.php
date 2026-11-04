<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_sekolah',
    'npsn',
    'jenjang',
    'pemerintah_daerah',
    'dinas_pendidikan',
    'alamat',
    'kota',
    'kode_pos',
    'no_telp',
    'email',
    'website',
    'nama_kepala_sekolah',
    'nip_kepala_sekolah',
])]
class ProfilSekolah extends Model
{
    use HasFactory;

    protected static ?self $cachedCurrent = null;

    /**
     * Get the singleton current school profile record.
     */
    public static function current(): self
    {
        if (static::$cachedCurrent !== null && ! app()->runningUnitTests()) {
            return static::$cachedCurrent;
        }

        $profile = static::first();

        if ($profile === null) {
            $profile = static::create([
                'nama_sekolah' => 'SMK Negeri Contoh',
                'npsn' => '20100001',
                'jenjang' => 'SMK',
                'pemerintah_daerah' => 'PEMERINTAH DAERAH PROVINSI JAWA BARAT',
                'dinas_pendidikan' => 'DINAS PENDIDIKAN DAN KEBUDAYAAN',
                'alamat' => 'Jl. Pendidikan No. 123',
                'kota' => 'Bandung',
                'kode_pos' => '40123',
                'no_telp' => '(022) 1234567',
                'email' => 'info@smknegericontoh.sch.id',
                'website' => 'www.smknegericontoh.sch.id',
                'nama_kepala_sekolah' => 'Drs. H. Rahmat Hidayat, M.M.Pd.',
                'nip_kepala_sekolah' => '19700101 199503 1 001',
            ]);
        }

        static::$cachedCurrent = $profile;

        return $profile;
    }

    /**
     * Clear cached instance.
     */
    public static function clearCache(): void
    {
        static::$cachedCurrent = null;
    }

    /**
     * Formatted full address string.
     */
    public function alamatLengkap(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->kota,
            $this->kode_pos ? "Kode Pos {$this->kode_pos}" : null,
        ]);

        return implode(', ', $parts);
    }
}
