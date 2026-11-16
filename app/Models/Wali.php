<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'nama_ayah', 'nama_ibu', 'nama_wali', 'hp_wali'])]
class Wali extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'wali';

    protected static function booted(): void
    {
        static::saving(function (Wali $wali): void {
            if (filled($wali->hp_wali)) {
                $wali->hp_wali = \App\Support\ImporSiswa\PembacaNilai::normalisasiHp((string) $wali->hp_wali) ?? trim((string) $wali->hp_wali);
            }
        });
    }

    /**
     * Student that owns this guardian record.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
