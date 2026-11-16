<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'jenis',
    'tahun_ajaran_asal',
    'tahun_ajaran_tujuan',
    'dibuat_oleh',
    'jumlah_siswa',
    'status',
])]
class ProsesKelasBatch extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const JENIS_NAIK_KELAS = 'naik_kelas';

    public const JENIS_KELULUSAN = 'kelulusan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $table = 'proses_kelas_batch';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_siswa' => 'integer',
        ];
    }

    /**
     * Academic year the process started from.
     */
    public function tahunAjaranAsal(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_asal');
    }

    /**
     * Academic year the process moved students to.
     */
    public function tahunAjaranTujuan(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_tujuan');
    }

    /**
     * User who executed the batch.
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Class history rows produced by this batch.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelas::class, 'batch_id');
    }
}
