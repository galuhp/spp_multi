<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['kategori', 'tipe', 'nama', 'tahun', 'tahun_ajaran', 'tingkat', 'nominal', 'keterangan'])]
class Spp extends Model
{
    use HasFactory;

    public const KATEGORI_SPP = 'SPP';

    public const KATEGORI_UANG_GEDUNG = 'Uang Gedung';

    public const KATEGORI_UANG_SERAGAM = 'Uang Seragam';

    public const KATEGORI_BIAYA_PRAKTIK = 'Biaya Praktik';

    public const KATEGORI_BIAYA_UJIAN = 'Biaya Ujian';

    public const KATEGORI_LAINNYA = 'Lain-lain';

    public const DEFAULT_KATEGORI_LIST = [
        self::KATEGORI_SPP,
        self::KATEGORI_UANG_GEDUNG,
        self::KATEGORI_UANG_SERAGAM,
        self::KATEGORI_BIAYA_PRAKTIK,
        self::KATEGORI_BIAYA_UJIAN,
        self::KATEGORI_LAINNYA,
    ];

    public const TIPE_BULANAN = 'bulanan';

    public const TIPE_SEKALI_BAYAR = 'sekali_bayar';

    public const TINGKAT_LIST = [
        '1' => 'I',
        '2' => 'II',
        '3' => 'III',
        '4' => 'IV',
        '5' => 'V',
        '6' => 'VI',
        '7' => 'VII',
        '8' => 'VIII',
        '9' => 'IX',
        '10' => 'X',
        '11' => 'XI',
        '12' => 'XII',
    ];

    /**
     * Normalize class level (Arabic or Roman I-XIII) to its numeric string representation.
     */
    public static function normalisasiTingkat(?string $tingkat): ?string
    {
        $tingkat = mb_strtoupper(trim((string) $tingkat));

        return match ($tingkat) {
            '1', 'I' => '1',
            '2', 'II' => '2',
            '3', 'III' => '3',
            '4', 'IV' => '4',
            '5', 'V' => '5',
            '6', 'VI' => '6',
            '7', 'VII' => '7',
            '8', 'VIII' => '8',
            '9', 'IX' => '9',
            '10', 'X' => '10',
            '11', 'XI' => '11',
            '12', 'XII' => '12',
            '13', 'XIII' => '13',
            default => is_numeric($tingkat) ? (string) $tingkat : null,
        };
    }

    public static function labelTingkat(?string $tingkat): string
    {
        $normal = self::normalisasiTingkat($tingkat);

        return self::TINGKAT_LIST[$normal] ?? ($normal ?? '-');
    }

    /**
     * Find the SPP tariff for a level and academic year.
     */
    public static function untukTingkat(?string $tingkat, ?string $tahunAjaran = null): ?self
    {
        $tingkat = self::normalisasiTingkat($tingkat);

        if ($tingkat === null) {
            return null;
        }

        if ($tahunAjaran) {
            $exact = self::query()
                ->where('kategori', self::KATEGORI_SPP)
                ->where('tipe', self::TIPE_BULANAN)
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('tingkat', $tingkat)
                ->first();

            if ($exact) {
                return $exact;
            }
        }

        // Fallback: match by tingkat without year constraint
        $byTingkat = self::query()
            ->where('kategori', self::KATEGORI_SPP)
            ->where('tipe', self::TIPE_BULANAN)
            ->where('tingkat', $tingkat)
            ->orderByDesc('tahun_ajaran')
            ->first();

        if ($byTingkat) {
            return $byTingkat;
        }

        $byGlobalYear = self::query()
            ->where('kategori', self::KATEGORI_SPP)
            ->where('tipe', self::TIPE_BULANAN)
            ->when($tahunAjaran, fn (Builder $query): Builder => $query->where('tahun_ajaran', $tahunAjaran))
            ->where(function (Builder $query): void {
                $query->whereNull('tingkat')->orWhere('tingkat', '');
            })
            ->orderByDesc('tahun_ajaran')
            ->first();

        if ($byGlobalYear) {
            return $byGlobalYear;
        }

        $byGlobal = self::query()
            ->where('kategori', self::KATEGORI_SPP)
            ->where('tipe', self::TIPE_BULANAN)
            ->where(function (Builder $query): void {
                $query->whereNull('tingkat')->orWhere('tingkat', '');
            })
            ->orderByDesc('tahun_ajaran')
            ->first();

        if ($byGlobal) {
            return $byGlobal;
        }

        return self::query()
            ->where('kategori', self::KATEGORI_SPP)
            ->where('tipe', self::TIPE_BULANAN)
            ->first();
    }

    /**
     * Non-SPP tariffs that apply to a level, with fallback if year not specified.
     *
     * @return Collection<int, self>
     */
    public static function nonSppUntukTingkat(?string $tingkat, ?string $tahunAjaran = null): Collection
    {
        $tingkat = self::normalisasiTingkat($tingkat);

        if ($tingkat === null) {
            return collect();
        }

        if ($tahunAjaran) {
            $exact = self::query()
                ->where('kategori', '!=', self::KATEGORI_SPP)
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('tingkat', $tingkat)
                ->orderBy('id')
                ->get();

            if ($exact->isNotEmpty()) {
                return $exact;
            }
        }

        return self::query()
            ->where('kategori', '!=', self::KATEGORI_SPP)
            ->where('tingkat', $tingkat)
            ->orderBy('id')
            ->get();
    }

    /**
     * Global monthly non-SPP tariffs (no level restriction) for an academic year.
     *
     * @return Collection<int, self>
     */
    public static function nonSppGlobalBulanan(int $tahunMulai): Collection
    {
        return self::query()
            ->where('kategori', '!=', self::KATEGORI_SPP)
            ->whereNull('tingkat')
            ->where('tipe', self::TIPE_BULANAN)
            ->where(function (Builder $query) use ($tahunMulai): void {
                $query->where('tahun_ajaran', $tahunMulai.'/'.($tahunMulai + 1))
                    ->orWhere('tahun', $tahunMulai);
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Find the SPP tariff that matches the class level and academic year (falling back to the active academic year).
     */
    public static function untukKelas(?Kelas $kelas, ?string $namaTahunAjaran = null): ?self
    {
        if (! $kelas) {
            return null;
        }

        $kelas->loadMissing('tahunAjaran');

        $tahunAjaran = $namaTahunAjaran
            ?? $kelas->tahunAjaran?->nama
            ?? TahunAjaran::query()->where('aktif', true)->value('nama')
            ?? TahunAjaran::query()->orderByDesc('nama')->value('nama');

        return self::untukTingkat($kelas->tingkat, $tahunAjaran);
    }

    /**
     * Resolve the current tariff for a student, with a legacy fallback.
     */
    public static function untukSiswa(Siswa $siswa): ?self
    {
        $siswa->loadMissing(['kelas.tahunAjaran', 'spp']);

        return self::untukKelas($siswa->kelas) ?? $siswa->spp;
    }

    /**
     * Starting calendar year of this tariff's academic year.
     */
    public function tahunMulai(): int
    {
        return Pembayaran::tahunMulaiTarif($this);
    }

    /**
     * Determine if this tariff is paid monthly.
     */
    public function isBulanan(): bool
    {
        return ($this->tipe ?? self::TIPE_BULANAN) === self::TIPE_BULANAN;
    }

    /**
     * Alias for isBulanan (backwards compatibility).
     */
    public function isSppBulanan(): bool
    {
        return $this->isBulanan();
    }

    /**
     * Determine if this tariff is a one-off / installment fee.
     */
    public function isSekaliBayar(): bool
    {
        return ($this->tipe ?? self::TIPE_BULANAN) === self::TIPE_SEKALI_BAYAR;
    }

    /**
     * Get display label for this tariff.
     */
    public function namaLengkap(): string
    {
        $label = $this->nama ?: $this->kategori;

        if (filled($this->tingkat)) {
            $label .= ' Tingkat '.self::labelTingkat($this->tingkat);
        }

        return "{$label} (TA {$this->tahun_ajaran})";
    }

    /**
     * Badge CSS class for the category.
     */
    public function badgeClass(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_SPP => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300 border-sky-200/80 dark:border-sky-400/25',
            self::KATEGORI_UANG_GEDUNG => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 border-amber-200/80 dark:border-amber-400/25',
            self::KATEGORI_UANG_SERAGAM => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-400/25',
            self::KATEGORI_BIAYA_PRAKTIK => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300 border-cyan-200/80 dark:border-cyan-400/25',
            self::KATEGORI_BIAYA_UJIAN => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300 border-rose-200/80 dark:border-rose-400/25',
            default => 'bg-slate-50 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600',
        };
    }

    /**
     * Format nominal into Rupiah string.
     */
    public function formattedNominal(): string
    {
        $rp = 'Rp '.number_format($this->nominal, 0, ',', '.');

        return $this->isBulanan() ? $rp.' / bulan' : $rp;
    }

    /**
     * Scope query to SPP tariffs only.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSpp(Builder $query): Builder
    {
        return $query->where('kategori', self::KATEGORI_SPP);
    }

    /**
     * Scope query to non-SPP tariffs only.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNonSpp(Builder $query): Builder
    {
        return $query->where('kategori', '!=', self::KATEGORI_SPP);
    }

    /**
     * Get students assigned to this SPP tariff.
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    /**
     * Get payment transactions for this SPP tariff.
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Government-covered markers for this specific fee.
     */
    public function tanggunganPemerintah(): HasMany
    {
        return $this->hasMany(SiswaTanggunganPemerintah::class);
    }
}
