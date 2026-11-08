<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nomor_transaksi',
    'tanggal',
    'kategori',
    'keperluan',
    'nominal',
    'penerima',
    'metode_pembayaran',
    'catatan',
    'bukti_transaksi',
    'user_id',
    'tahun_ajaran_id',
    'void_at',
    'void_oleh',
    'void_alasan',
])]
class PengeluaranSekolah extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_sekolahs';

    public const KATEGORI_OPERASIONAL = 'Operasional Kantor';

    public const KATEGORI_GAJI = 'Gaji & Honor Guru/Staf';

    public const KATEGORI_ATK = 'ATK & Percetakan';

    public const KATEGORI_SARPRAS = 'Sarana & Prasarana';

    public const KATEGORI_KEGIATAN = 'Kegiatan Pembelajaran & Siswa';

    public const KATEGORI_UTILITAS = 'Listrik, Air & Internet';

    public const KATEGORI_PEMELIHARAAN = 'Pemeliharaan & Kebersihan';

    public const KATEGORI_KONSUMSI = 'Konsumsi Rapat/Tamu';

    public const KATEGORI_LAINNYA = 'Lain-lain';

    public const KATEGORI_LIST = [
        self::KATEGORI_OPERASIONAL => 'Operasional Kantor',
        self::KATEGORI_GAJI => 'Gaji & Honor Guru/Staf',
        self::KATEGORI_ATK => 'ATK & Percetakan',
        self::KATEGORI_SARPRAS => 'Sarana & Prasarana',
        self::KATEGORI_KEGIATAN => 'Kegiatan Pembelajaran & Siswa',
        self::KATEGORI_UTILITAS => 'Listrik, Air & Internet',
        self::KATEGORI_PEMELIHARAAN => 'Pemeliharaan & Kebersihan',
        self::KATEGORI_KONSUMSI => 'Konsumsi Rapat/Tamu',
        self::KATEGORI_LAINNYA => 'Lain-lain',
    ];

    public const METODE_TUNAI = 'Tunai';

    public const METODE_TRANSFER = 'Transfer Bank';

    public const METODE_LIST = [
        self::METODE_TUNAI => 'Tunai',
        self::METODE_TRANSFER => 'Transfer Bank',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'void_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PengeluaranSekolah $pengeluaran): void {
            if (blank($pengeluaran->nomor_transaksi)) {
                $pengeluaran->nomor_transaksi = static::buatNomorTransaksi($pengeluaran->tanggal);
            }

            if (blank($pengeluaran->user_id)) {
                $pengeluaran->user_id = (int) auth()->id();
            }

            if (blank($pengeluaran->tahun_ajaran_id)) {
                $pengeluaran->tahun_ajaran_id = TahunAjaran::query()->where('aktif', true)->value('id')
                    ?? TahunAjaran::query()->orderByDesc('nama')->value('id');
            }
        });
    }

    /**
     * Generate unique transaction code: EXP-YYYYMMDD-XXXX
     */
    public static function buatNomorTransaksi(?\DateTimeInterface $tanggal = null): string
    {
        $tgl = $tanggal ? Carbon::parse($tanggal) : now();
        $prefix = 'EXP-'.$tgl->format('Ymd').'-';

        $lastNumber = static::query()
            ->where('nomor_transaksi', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('nomor_transaksi');

        $urutan = 1;

        if ($lastNumber && preg_match('/-(\d+)$/', $lastNumber, $m)) {
            $urutan = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * User who cancelled this expenditure.
     */
    public function voidOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_oleh');
    }

    /**
     * Only expenditures that have not been cancelled.
     */
    public function scopeTidakBatal(Builder $query): Builder
    {
        return $query->whereNull('void_at');
    }

    public function isVoid(): bool
    {
        return $this->void_at !== null;
    }

    public function labelKategori(): string
    {
        return self::KATEGORI_LIST[$this->kategori] ?? ($this->kategori ?: '-');
    }

    public function formattedNominal(): string
    {
        return 'Rp '.number_format((float) $this->nominal, 0, ',', '.');
    }
}
