<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'nama_ayah', 'nama_ibu', 'nama_wali', 'hp_wali'])]
class Wali extends Model
{
    use HasFactory;

    protected $table = 'wali';

    /**
     * Student that owns this guardian record.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
