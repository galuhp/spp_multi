<?php

namespace App\Models;

use App\Support\Pembayaran\NomorKuitansiService;
use App\Support\QrCodeSvg;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'gabungan_id',
    'gabungan_baris_id',
    'void_oleh',
    'void_alasan',
    'void_at',
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
     * Starting calendar year of a tariff's academic year.
     *
     * The academic year `2026/2027` starts in July 2026 and ends in June 2027.
     */
    public static function tahunMulaiTarif(?Spp $tarif): int
    {
        if (! $tarif) {
            return (int) now()->year;
        }

        if (filled($tarif->tahun_ajaran) && preg_match('/^\s*(\d{4})\s*[\/-]\s*(\d{4})\s*$/', (string) $tarif->tahun_ajaran, $matches)) {
            return (int) $matches[1];
        }

        return (int) $tarif->tahun;
    }

    /**
     * Calendar year of a month within an academic year.
     */
    public static function tahunUntukBulan(string $bulan, int $tahunMulai): int
    {
        $index = array_search($bulan, self::TAHUN_AJARAN_BULAN_LIST, true);

        return ($index !== false && $index >= 6) ? $tahunMulai + 1 : $tahunMulai;
    }

    /**
     * All academic-year months paired with their calendar year.
     *
     * @return array<int, array{bulan: string, tahun: int}>
     */
    public static function bulanTahunAjaran(int $tahunMulai): array
    {
        return array_map(
            fn (string $bulan): array => [
                'bulan' => $bulan,
                'tahun' => self::tahunUntukBulan($bulan, $tahunMulai),
            ],
            self::TAHUN_AJARAN_BULAN_LIST,
        );
    }

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
            'void_at' => 'datetime',
        ];
    }

    /**
     * Only payments that have not been voided.
     */
    public function scopeTidakVoid(Builder $query): Builder
    {
        return $query->whereNull('void_at');
    }

    public function isVoid(): bool
    {
        return $this->void_at !== null;
    }

    /**
     * Generate unique transaction code.
     */
    public static function generateKodeTransaksi(): string
    {
        return app(NomorKuitansiService::class)->berikutnya();
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
     * Mass payment batch that produced this payment.
     */
    public function gabungan(): BelongsTo
    {
        return $this->belongsTo(PembayaranGabungan::class, 'gabungan_id');
    }

    /**
     * Mass payment line that produced this payment.
     */
    public function gabunganBaris(): BelongsTo
    {
        return $this->belongsTo(PembayaranGabunganBaris::class, 'gabungan_baris_id');
    }

    /**
     * User who voided this payment.
     */
    public function pemberiVoid(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_oleh');
    }

    /**
     * Format amount into Rupiah currency.
     */
    public function formattedJumlahBayar(): string
    {
        return 'Rp '.number_format($this->jumlah_bayar, 0, ',', '.');
    }

    /**
     * Get display title for the payment item / post.
     */
    public function namaPos(): string
    {
        $kategori = $this->spp?->kategori ?? 'SPP';
        $nama = trim((string) ($this->spp?->nama ?? ''));

        if ($this->bulan_dibayar) {
            $isSppBaku = strcasecmp($kategori, 'SPP') === 0
                && ($nama === '' || strcasecmp($nama, 'SPP') === 0 || strcasecmp($nama, 'SPP Bulanan') === 0 || strcasecmp($nama, 'Biaya Sekolah per bulan') === 0);

            $prefix = $isSppBaku ? 'SPP' : ($nama !== '' ? $nama : $kategori);

            return "{$prefix} Bulan {$this->bulan_dibayar} {$this->tahun_dibayar}";
        }

        $label = $nama !== '' ? $nama : "{$kategori} TA {$this->spp?->tahun_ajaran}";

        return $label;
    }

    /**
     * Retrieve all payments recorded in the same checkout batch / receipt code for this student.
     *
     * @return Collection<int, self>
     */
    public function relatedBatchPayments(): Collection
    {
        if (filled($this->kode_transaksi)) {
            $byKode = static::query()
                ->with('spp')
                ->where('kode_transaksi', $this->kode_transaksi)
                ->when(! $this->isVoid(), fn (Builder $q): Builder => $q->whereNull('void_at'))
                ->orderBy('id')
                ->get();

            if ($byKode->isNotEmpty()) {
                return $byKode;
            }
        }

        $query = static::query()
            ->with('spp')
            ->where('siswa_id', $this->siswa_id)
            ->whereNull('void_at')
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
     * Uses kode_transaksi + siswa_id so all rows sharing the same receipt number produce an identical token.
     */
    public function verificationToken(): string
    {
        $secret = (string) config('app.key', 'spp-verification-key');
        $payload = 'bukti-spp:'.$this->siswa_id.':'.$this->kode_transaksi;

        return substr(hash_hmac('sha256', $payload, $secret), 0, 16);
    }

    /**
     * Legacy token based on the row primary key (kept for backward compatibility with already printed QR codes).
     */
    public function legacyVerificationToken(): string
    {
        $secret = (string) config('app.key', 'spp-verification-key');
        $payload = 'bukti-spp:'.$this->id.':'.$this->kode_transaksi;

        return substr(hash_hmac('sha256', $payload, $secret), 0, 16);
    }

    /**
     * Determine whether the given token matches this payment's verification token.
     */
    public function hasValidVerificationToken(string $token): bool
    {
        $normalized = strtolower(trim($token));

        return hash_equals($this->verificationToken(), $normalized)
            || hash_equals($this->legacyVerificationToken(), $normalized);
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
