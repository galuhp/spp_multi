<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'deskripsi'])]
class Jurusan extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * Get all classes for this department.
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
