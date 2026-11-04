<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kategori', 'tipe', 'nama', 'tahun', 'tahun_ajaran', 'nominal', 'keterangan'])]
class Spp extends Model
{
    use HasFactory;

    public const KATEGORI_SPP = 'SPP';

    public const KATEGORI_UANG_GEDUNG = 'Uang Gedung';

    public const KATEGORI_UANG_SERAGAM = 'Uang Seragam';

    public const KATEGORI_BIAYA_PRAKTIK = 'Biaya Praktik';

    public const KATEGORI_BIAYA_UJIAN = 'Biaya Ujian';

    public const DEFAULT_KATEGORI_LIST = [
        self::KATEGORI_SPP,
        self::KATEGORI_UANG_GEDUNG,
        self::KATEGORI_UANG_SERAGAM,
        self::KATEGORI_BIAYA_PRAKTIK,
        self::KATEGORI_BIAYA_UJIAN,
    ];

    public const TIPE_BULANAN = 'bulanan';

    public const TIPE_SEKALI_BAYAR = 'sekali_bayar';

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
        if ($this->nama) {
            return "{$this->nama} (TA {$this->tahun_ajaran})";
        }

        return "{$this->kategori} TA {$this->tahun_ajaran}";
    }

    /**
     * Badge CSS class for the category.
     */
    public function badgeClass(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_SPP => 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 border-purple-200/80 dark:border-purple-400/25',
            self::KATEGORI_UANG_GEDUNG => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 border-amber-200/80 dark:border-amber-400/25',
            self::KATEGORI_UANG_SERAGAM => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-400/25',
            self::KATEGORI_BIAYA_PRAKTIK => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300 border-cyan-200/80 dark:border-cyan-400/25',
            self::KATEGORI_BIAYA_UJIAN => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300 border-rose-200/80 dark:border-rose-400/25',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600',
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
}
