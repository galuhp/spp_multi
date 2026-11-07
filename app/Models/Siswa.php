<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

#[Fillable([
    'user_id',
    'kelas_id',
    'spp_id',
    'nisn',
    'nik',
    'nis',
    'nama',
    'tempat_lahir',
    'tanggal_lahir',
    'agama',
    'jenis_kelamin',
    'alamat',
    'tanggal_masuk',
    'tahun_masuk',
    'status',
    'no_telp',
    'is_archived',
    'archived_at',
    'tanggal_lulus',
])]
class Siswa extends Model
{
    use HasFactory;

    /**
     * Auto-fill tahun_masuk, sync initial RiwayatKelas, and prevent deleting a student with payment history.
     */
    protected static function booted(): void
    {
        static::saving(function (Siswa $siswa): void {
            if (filled($siswa->no_telp)) {
                $siswa->no_telp = \App\Support\ImporSiswa\PembacaNilai::normalisasiHp((string) $siswa->no_telp) ?? trim((string) $siswa->no_telp);
            }
        });

        static::creating(function (Siswa $siswa): void {
            if (blank($siswa->tahun_masuk)) {
                if ($siswa->tanggal_masuk) {
                    $siswa->tahun_masuk = (int) $siswa->tanggal_masuk->format('Y');
                } elseif ($siswa->spp_id && ($spp = Spp::find($siswa->spp_id))) {
                    $siswa->tahun_masuk = Pembayaran::tahunMulaiTarif($spp);
                } else {
                    $siswa->tahun_masuk = (int) now()->year;
                }
            }
        });

        static::created(function (Siswa $siswa): void {
            if (! $siswa->kelas_id) {
                return;
            }

            $kelas = Kelas::find($siswa->kelas_id);
            $tahunAjaranId = $kelas?->tahun_ajaran_id
                ?: TahunAjaran::query()->where('aktif', true)->value('id')
                ?: TahunAjaran::query()->orderByDesc('nama')->value('id');

            if ($tahunAjaranId) {
                RiwayatKelas::query()->firstOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                    ],
                    [
                        'kelas_id' => $siswa->kelas_id,
                        'spp_id' => $siswa->spp_id,
                        'status' => $siswa->status === 'lulus'
                            ? RiwayatKelas::STATUS_LULUS
                            : RiwayatKelas::STATUS_AKTIF,
                    ],
                );
            }
        });

        static::deleting(function (Siswa $siswa): void {
            if ($siswa->pembayarans()->exists()) {
                throw new RuntimeException('Siswa dengan riwayat pembayaran tidak dapat dihapus. Arsipkan data siswa.');
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tahun_masuk' => 'integer',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'tanggal_lulus' => 'date',
        ];
    }

    /**
     * Only students that are active and not archived.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_archived', false)->where('status', 'aktif');
    }

    /**
     * Exclude archived students.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTanpaArsip(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Only archived students.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArsip(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Determine whether this student is archived.
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * User account associated with this student (optional login).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Class that this student belongs to.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * SPP tariff assigned to this student.
     */
    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    /**
     * Payment records for this student.
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Government-covered fee markers for this student.
     */
    public function tanggunganPemerintah(): HasMany
    {
        return $this->hasMany(SiswaTanggunganPemerintah::class);
    }

    /**
     * Guardian data of this student.
     */
    public function wali(): HasOne
    {
        return $this->hasOne(Wali::class);
    }

    /**
     * Class history of this student per academic year.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelas::class);
    }

    /**
     * Total amount paid by this student.
     */
    public function totalDibayar(): int
    {
        return (int) $this->pembayarans()->whereNull('void_at')->sum('jumlah_bayar');
    }

    /**
     * Check if a specific month and year has been fully paid.
     */
    public function sudahBayar(string $bulan, int $tahun): bool
    {
        $nominal = (int) ($this->spp?->nominal ?? 0);

        if ($nominal <= 0) {
            return false;
        }

        return (int) $this->pembayarans()
            ->whereNull('void_at')
            ->where('spp_id', $this->spp_id)
            ->where('bulan_dibayar', $bulan)
            ->where('tahun_dibayar', $tahun)
            ->sum('jumlah_bayar') >= $nominal;
    }
}
