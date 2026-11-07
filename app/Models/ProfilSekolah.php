<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'app_name',
    'logo',
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
                'app_name' => 'E-SPP SEKOLAH',
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
                'website' => 'https://www.smknegericontoh.sch.id',
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
     * Application name shown on the panel, login page, and documents.
     */
    public function appName(): string
    {
        return $this->app_name ?: 'E-SPP SEKOLAH';
    }

    /**
     * Public URL of the uploaded school logo.
     */
    public function logoUrl(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo);
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

    public const JENJANG_LIST = [
        'SD' => 'SD (Kelas 1 – 6)',
        'MI' => 'MI (Kelas 1 – 6)',
        'SMP' => 'SMP (Kelas 7 – 9)',
        'MTs' => 'MTs (Kelas 7 – 9)',
        'SMA' => 'SMA (Kelas 10 – 12 / X – XII)',
        'MA' => 'MA (Kelas 10 – 12 / X – XII)',
        'SMK' => 'SMK (Kelas 10 – 12 / X – XII)',
        'SEMUA' => 'Semua Jenjang / Terpadu (Kelas 1 – 12)',
    ];

    /**
     * Get the normalized school education level (SD, MI, SMP, MTs, SMA, MA, SMK, or SEMUA).
     */
    public function normalizedJenjang(): string
    {
        $raw = mb_strtoupper(trim((string) $this->jenjang));

        return match ($raw) {
            'SD', 'SD/MI' => 'SD',
            'MI' => 'MI',
            'SMP', 'SMP/MTS' => 'SMP',
            'MTS' => 'MTs',
            'SMA', 'SMA/MA' => 'SMA',
            'MA' => 'MA',
            'SMK' => 'SMK',
            'SEMUA', 'TERPADU', 'ALL' => 'SEMUA',
            default => 'SMK',
        };
    }

    /**
     * Class level options (`KelasForm`, `KelasTable`, `KelasRelationManager`) filtered by the configured school jenjang.
     *
     * @return array<string, string>
     */
    public static function tingkatKelasOptions(?string $jenjang = null): array
    {
        $level = $jenjang !== null
            ? (new self(['jenjang' => $jenjang]))->normalizedJenjang()
            : static::current()->normalizedJenjang();

        $sd = [
            '1' => 'Kelas 1 (I - SD/MI)',
            '2' => 'Kelas 2 (II - SD/MI)',
            '3' => 'Kelas 3 (III - SD/MI)',
            '4' => 'Kelas 4 (IV - SD/MI)',
            '5' => 'Kelas 5 (V - SD/MI)',
            '6' => 'Kelas 6 (VI - SD/MI)',
        ];

        $smp = [
            '7' => 'Kelas 7 (VII - SMP/MTs)',
            '8' => 'Kelas 8 (VIII - SMP/MTs)',
            '9' => 'Kelas 9 (IX - SMP/MTs)',
        ];

        $smaSmk = [
            'X' => 'Kelas X (10 - SMA/SMK/MA)',
            'XI' => 'Kelas XI (11 - SMA/SMK/MA)',
            'XII' => 'Kelas XII (12 - SMA/SMK/MA)',
        ];

        return match ($level) {
            'SD', 'MI' => $sd,
            'SMP', 'MTs' => $smp,
            'SMA', 'MA', 'SMK' => $smaSmk,
            default => $sd + $smp + $smaSmk,
        };
    }

    /**
     * Numeric-keyed level options (`SppForm`, `SppsTable`, `LaporanTunggakan`, `RekapPotensiPenerimaan`, `EntriPembayaranKasir`, `CreatePembayaranGabungan`)
     * filtered by the configured school jenjang.
     *
     * @return array<string, string>
     */
    public static function tingkatSppOptions(?string $jenjang = null): array
    {
        $level = $jenjang !== null
            ? (new self(['jenjang' => $jenjang]))->normalizedJenjang()
            : static::current()->normalizedJenjang();

        $sd = [
            '1' => 'Kelas 1 (I)',
            '2' => 'Kelas 2 (II)',
            '3' => 'Kelas 3 (III)',
            '4' => 'Kelas 4 (IV)',
            '5' => 'Kelas 5 (V)',
            '6' => 'Kelas 6 (VI)',
        ];

        $smp = [
            '7' => 'Kelas 7 (VII)',
            '8' => 'Kelas 8 (VIII)',
            '9' => 'Kelas 9 (IX)',
        ];

        $smaSmk = [
            '10' => 'Kelas X (10)',
            '11' => 'Kelas XI (11)',
            '12' => 'Kelas XII (12)',
        ];

        return match ($level) {
            'SD', 'MI' => $sd,
            'SMP', 'MTs' => $smp,
            'SMA', 'MA', 'SMK' => $smaSmk,
            default => $sd + $smp + $smaSmk,
        };
    }

    /**
     * Final/graduation grade values (numeric and Roman) for the configured school jenjang.
     *
     * @return array<int, string>
     */
    public static function tingkatAkhirValues(?string $jenjang = null): array
    {
        $level = $jenjang !== null
            ? (new self(['jenjang' => $jenjang]))->normalizedJenjang()
            : static::current()->normalizedJenjang();

        return match ($level) {
            'SD', 'MI' => ['6', 'VI'],
            'SMP', 'MTs' => ['9', 'IX'],
            'SMA', 'MA', 'SMK' => ['12', '13', 'XII', 'XIII'],
            default => ['6', 'VI', '9', 'IX', '12', '13', 'XII', 'XIII'],
        };
    }
}

