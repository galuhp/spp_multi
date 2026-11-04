<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'tanggal_mulai', 'tanggal_selesai', 'aktif'])]
class TahunAjaran extends Model
{
    use HasFactory;

    /**
     * Keep at most one academic year active.
     */
    protected static function booted(): void
    {
        static::saving(function (TahunAjaran $tahunAjaran): void {
            if (! $tahunAjaran->aktif) {
                return;
            }

            static::query()
                ->when(
                    $tahunAjaran->exists,
                    fn (Builder $query): Builder => $query->whereKeyNot($tahunAjaran->getKey()),
                )
                ->where('aktif', true)
                ->update(['aktif' => false]);
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
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Determine whether this academic year is used by classes or class history.
     */
    public function sedangDipakai(): bool
    {
        return $this->kelas()->exists() || $this->riwayatKelas()->exists();
    }

    /**
     * Classes that belong to this academic year.
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Class history rows that belong to this academic year.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelas::class);
    }
}
