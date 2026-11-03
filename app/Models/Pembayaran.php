<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kode_transaksi',
    'user_id',
    'siswa_id',
    'spp_id',
    'tgl_bayar',
    'bulan_dibayar',
    'tahun_dibayar',
    'jumlah_bayar',
    'metode_pembayaran',
    'catatan',
])]
class Pembayaran extends Model
{
    use HasFactory;

    public const BULAN_LIST = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];

    public const TAHUN_AJARAN_BULAN_LIST = [
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_bayar' => 'date',
            'tahun_dibayar' => 'integer',
            'jumlah_bayar' => 'integer',
        ];
    }

    /**
     * Generate unique transaction code.
     */
    public static function generateKodeTransaksi(): string
    {
        $prefix = 'SPP-' . date('Ymd') . '-';
        $latest = static::query()
            ->where('kode_transaksi', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->kode_transaksi, -4);
            $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    /**
     * Staff/user that received and recorded the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Student who made the payment.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * SPP tariff reference for this payment.
     */
    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    /**
     * Format amount into Rupiah currency.
     */
    public function formattedJumlahBayar(): string
    {
        return 'Rp ' . number_format($this->jumlah_bayar, 0, ',', '.');
    }
}
