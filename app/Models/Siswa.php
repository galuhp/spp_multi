<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
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
        return (int) $this->pembayarans()->sum('jumlah_bayar');
    }

    /**
     * Check if a specific month and year has been paid.
     */
    public function sudahBayar(string $bulan, int $tahun): bool
    {
        return $this->pembayarans()
            ->where('bulan_dibayar', $bulan)
            ->where('tahun_dibayar', $tahun)
            ->exists();
    }
}
