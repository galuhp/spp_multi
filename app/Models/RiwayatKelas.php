<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'kelas_id', 'tahun_ajaran_id', 'spp_id', 'status', 'batch_id'])]
class RiwayatKelas extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NAIK = 'naik';

    public const STATUS_TINGGAL = 'tinggal';

    public const STATUS_LULUS = 'lulus';

    protected $table = 'riwayat_kelas';

    /**
     * Student that owns this class history row.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Class assigned in this academic year.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Academic year of this class history row.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * SPP tariff assigned in this academic year.
     */
    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    /**
     * Batch that produced this history row.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProsesKelasBatch::class, 'batch_id');
    }
}
