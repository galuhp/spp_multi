<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'siswa_id',
    'cakupan',
    'spp_id',
    'tahun_ajaran_id',
    'persentase',
    'jenis',
    'nomor_sk',
    'keterangan',
    'mulai',
    'selesai',
    'is_aktif',
    'created_by',
])]
class SiswaSubsidi extends Model
{
    use HasFactory;

    public const CAKUPAN_SPP = 'spp';
    public const CAKUPAN_POS = 'pos';
    public const CAKUPAN_SEMUA = 'semua';

    public const JENIS_ANAK_GURU = 'anak_guru';
    public const JENIS_PRESTASI = 'prestasi';
    public const JENIS_AFIRMASI = 'afirmasi';
    public const JENIS_YAYASAN = 'yayasan';
    public const JENIS_LAINNYA = 'lainnya';

    protected $table = 'siswa_subsidies';

    protected function casts(): array
    {
        return [
            'persentase' => 'integer',
            'mulai' => 'date',
            'selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subsidi): void {
            $subsidi->created_by ??= auth()->id();
        });

        static::saving(function (self $subsidi): void {
            if ($subsidi->cakupan === self::CAKUPAN_SPP || $subsidi->cakupan === self::CAKUPAN_SEMUA) {
                $subsidi->spp_id = null;
            }

            if ($subsidi->cakupan === self::CAKUPAN_POS && ! $subsidi->spp_id) {
                throw ValidationException::withMessages([
                    'spp_id' => 'Pilih pos biaya yang mendapatkan subsidi.',
                ]);
            }

            if ($subsidi->persentase < 1 || $subsidi->persentase > 100) {
                throw ValidationException::withMessages([
                    'persentase' => 'Persentase subsidi harus antara 1% hingga 100%.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function cakupanOptions(): array
    {
        return [
            self::CAKUPAN_SPP => 'SPP Bulanan',
            self::CAKUPAN_POS => 'Pos Biaya Tertentu (Non-SPP)',
            self::CAKUPAN_SEMUA => 'Semua Biaya (SPP & Non-SPP)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function jenisOptions(): array
    {
        return [
            self::JENIS_ANAK_GURU => 'Anak Guru / Karyawan',
            self::JENIS_PRESTASI => 'Beasiswa Prestasi',
            self::JENIS_AFIRMASI => 'Afirmasi / Keringanan Ekonomi',
            self::JENIS_YAYASAN => 'Bantuan Yayasan',
            self::JENIS_LAINNYA => 'Khusus / Lainnya',
        ];
    }

    public function labelJenis(): string
    {
        return static::jenisOptions()[$this->jenis] ?? ucfirst((string) $this->jenis);
    }

    public function labelCakupan(): string
    {
        return static::cakupanOptions()[$this->cakupan] ?? ucfirst((string) $this->cakupan);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Check if this subsidy is active at a given date and academic year.
     */
    public function isAktifPada(?CarbonInterface $tanggal = null, ?int $tahunAjaranId = null): bool
    {
        if (! $this->is_aktif) {
            return false;
        }

        if ($this->tahun_ajaran_id !== null && $tahunAjaranId !== null && (int) $this->tahun_ajaran_id !== (int) $tahunAjaranId) {
            return false;
        }

        if ($tanggal !== null) {
            if ($this->mulai && $tanggal->lt($this->mulai)) {
                return false;
            }

            if ($this->selesai && $tanggal->gt($this->selesai)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hitung sisa kewajiban bayar setelah dipotong subsidi n%.
     */
    public static function hitungKewajiban(int $nominal, int $persentase): int
    {
        $persen = max(0, min(100, $persentase));
        $potongan = (int) round(($nominal * $persen) / 100);

        return max(0, $nominal - $potongan);
    }

    /**
     * Hitung nominal potongan subsidi n%.
     */
    public static function hitungPotongan(int $nominal, int $persentase): int
    {
        $persen = max(0, min(100, $persentase));

        return (int) round(($nominal * $persen) / 100);
    }

    /**
     * Label ringkas subsidi.
     */
    public function labelRingkas(): string
    {
        $jenisLabel = self::jenisOptions()[$this->jenis] ?? $this->jenis;

        return "Subsidi {$this->persentase}% ({$jenisLabel})";
    }
}
