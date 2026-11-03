<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'aksi', 'subjek_tipe', 'subjek_id', 'deskripsi', 'data', 'ip_address'])]
class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Record an activity entry for the currently authenticated user.
     *
     * @param  array<string, mixed>  $data
     */
    public static function catat(string $aksi, string $deskripsi, ?Model $subjek = null, array $data = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'subjek_tipe' => $subjek ? class_basename($subjek) : null,
            'subjek_id' => $subjek?->getKey(),
            'deskripsi' => $deskripsi,
            'data' => $data === [] ? null : $data,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * User who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Badge color classes based on the action group.
     */
    public function badgeClass(): string
    {
        return match (true) {
            str_ends_with($this->aksi, '.dihapus') => 'bg-rose-100/90 text-rose-800 border border-rose-200/80 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-400/25',
            str_ends_with($this->aksi, '.diubah') => 'bg-amber-100/90 text-amber-800 border border-amber-200/80 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-400/25',
            default => 'bg-emerald-100/90 text-emerald-800 border border-emerald-200/80 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-400/25',
        };
    }
}
