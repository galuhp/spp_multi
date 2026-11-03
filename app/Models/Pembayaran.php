<?php

namespace App\Models;

use App\Support\QrCodeSvg;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Retrieve all payments recorded in the same checkout batch for this student.
     *
     * @return Collection<int, self>
     */
    public function relatedBatchPayments(): Collection
    {
        $query = static::query()
            ->with('spp')
            ->where('siswa_id', $this->siswa_id)
            ->whereDate('tgl_bayar', $this->tgl_bayar);

        if ($this->created_at !== null) {
            $query->where('created_at', '>=', $this->created_at->copy()->subMinutes(5))
                ->where('created_at', '<=', $this->created_at->copy()->addMinutes(5));
        } else {
            $query->whereKey($this->id);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Generate a deterministic HMAC-SHA256 verification token for public receipt links.
     */
    public function verificationToken(): string
    {
        $secret = (string) config('app.key', 'spp-verification-key');
        $payload = 'bukti-spp:' . $this->id . ':' . $this->kode_transaksi;

        return substr(hash_hmac('sha256', $payload, $secret), 0, 16);
    }

    /**
     * Determine whether the given token matches this payment's verification token.
     */
    public function hasValidVerificationToken(string $token): bool
    {
        return hash_equals($this->verificationToken(), strtolower($token));
    }

    /**
     * Build the public digital receipt URL embedded inside the printed QR code.
     */
    public function buktiUrl(): string
    {
        return route('bukti.pembayaran', [
            'kode' => $this->kode_transaksi,
            'token' => $this->verificationToken(),
        ]);
    }

    /**
     * Render an inline SVG QR code encoding the public verification URL.
     */
    public function qrCodeSvg(int $sizePx = 96): string
    {
        return QrCodeSvg::generate($this->buktiUrl(), $sizePx);
    }
}
