<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['jurusan_id', 'nama_kelas', 'tingkat'])]
class Kelas extends Model
{
    use HasFactory;

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
