<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'siswa_id',
    'cakupan',
    'spp_id',
    'mulai',
    'dicabut_at',
    'alasan',
    'created_by',
])]
class SiswaTanggunganPemerintah extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const CAKUPAN_SPP = 'spp';

    public const CAKUPAN_POS = 'pos';

    protected $table = 'siswa_tanggungan_pemerintah';

    protected static function booted(): void
    {
        static::creating(function (self $tanggungan): void {
            $tanggungan->created_by ??= auth()->id();
        });

        static::saving(function (self $tanggungan): void {
            if ($tanggungan->cakupan === self::CAKUPAN_SPP) {
                $tanggungan->spp_id = null;
            }

            if ($tanggungan->cakupan === self::CAKUPAN_POS && ! $tanggungan->spp_id) {
                throw ValidationException::withMessages([
                    'spp_id' => 'Pilih pos biaya yang ditanggung.',
                ]);
            }

            if ($tanggungan->dicabut_at !== null) {
                return;
            }

            $query = static::query()
                ->where('siswa_id', $tanggungan->siswa_id)
                ->where('cakupan', $tanggungan->cakupan)
                ->whereNull('dicabut_at')
                ->when(
                    $tanggungan->cakupan === self::CAKUPAN_POS,
                    fn (Builder $query): Builder => $query->where('spp_id', $tanggungan->spp_id),
                );

            if ($tanggungan->exists) {
                $query->whereKeyNot($tanggungan->getKey());
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'cakupan' => 'Sudah ada tanggungan pemerintah aktif untuk cakupan ini.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'dicabut_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function cakupanOptions(): array
    {
        return [
            self::CAKUPAN_SPP => 'SPP (semua tingkat)',
            self::CAKUPAN_POS => 'Pos biaya tertentu',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->whereNull('dicabut_at')
            ->whereDate('mulai', '<=', now()->toDateString());
    }

    public function isAktif(): bool
    {
        return $this->isAktifPada(now());
    }

    public function isAktifPada(CarbonInterface $tanggal): bool
    {
        if ($this->dicabut_at !== null && $this->dicabut_at->lte($tanggal)) {
            return false;
        }

        return $this->mulai->lte($tanggal);
    }

    public function cakupanLabel(): string
    {
        return self::cakupanOptions()[$this->cakupan] ?? $this->cakupan;
    }

    public function nominal(): int
    {
        if ($this->cakupan === self::CAKUPAN_POS) {
            return (int) ($this->spp?->nominal ?? 0);
        }

        $this->loadMissing('siswa.spp');

        return (int) ($this->siswa?->spp?->nominal ?? 0);
    }

    public function label(): string
    {
        if ($this->cakupan === self::CAKUPAN_POS) {
            return $this->spp?->namaLengkap() ?? 'Pos biaya';
        }

        return 'SPP';
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
