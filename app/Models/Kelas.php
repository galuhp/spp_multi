<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['jurusan_id', 'tahun_ajaran_id', 'wali_kelas_id', 'nama_kelas', 'tingkat'])]
class Kelas extends Model
{
    use HasFactory;

    /**
     * Academic year that owns this class.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Homeroom teacher (Wali Kelas) assigned to this class.
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /**
     * Get the department that owns this class.
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    /**
     * Get all students enrolled in this class.
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
}
