<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'tanggal',
    'sumber',
    'metode',
    'kelas_id',
    'total_diterima',
    'status',
    'catatan',
    'idempotency_token',
    'dibuat_oleh',
    'diposting_oleh',
    'diposting_at',
    'void_oleh',
    'void_alasan',
    'void_at',
])]
class PembayaranGabungan extends Model
{
    use HasFactory;

    /**
     * Derive the payment method from the source so the form does not need a separate field.
     */
    protected static function booted(): void
    {
        static::saving(function (PembayaranGabungan $gabungan): void {
            $gabungan->metode = $gabungan->sumber === self::SUMBER_TRANSFER_BANK
                ? self::METODE_TRANSFER
                : self::METODE_TUNAI;
        });
    }

    public const SUMBER_SETORAN_KELAS = 'setoran_kelas';

    public const SUMBER_TRANSFER_BANK = 'transfer_bank';

    public const SUMBER_CATATAN_MANUAL = 'catatan_manual';

    public const METODE_TUNAI = 'tunai';

    public const METODE_TRANSFER = 'transfer';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_MEMPROSES = 'memproses';

    public const STATUS_DIPOSTING = 'diposting';

    public const STATUS_VOID = 'void';

    protected $table = 'pembayaran_gabungan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_diterima' => 'integer',
            'diposting_at' => 'datetime',
            'void_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function sumberOptions(): array
    {
        return [
            self::SUMBER_SETORAN_KELAS => 'Setoran Kelas',
            self::SUMBER_TRANSFER_BANK => 'Rekap Transfer Bank',
            self::SUMBER_CATATAN_MANUAL => 'Catatan Manual',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function metodeOptions(): array
    {
        return [
            self::METODE_TUNAI => 'Tunai',
            self::METODE_TRANSFER => 'Transfer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_MEMPROSES => 'Memproses',
            self::STATUS_DIPOSTING => 'Diposting',
            self::STATUS_VOID => 'Void',
        ];
    }

    public function baris(): HasMany
    {
        return $this->hasMany(PembayaranGabunganBaris::class, 'gabungan_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pemosting(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diposting_oleh');
    }

    public function pemberiVoid(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_oleh');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_DIPOSTING;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function totalBaris(): int
    {
        return (int) $this->baris()->sum('jumlah');
    }
}
