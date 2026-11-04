<?php

namespace App\Support\Pembayaran;

use App\Models\NomorKuitansiUrutan;
use Illuminate\Support\Facades\DB;

class NomorKuitansiService
{
    /**
     * Generate the next receipt number using a locked per-year sequence.
     */
    public function berikutnya(?int $tahun = null, string $seri = 'SPP'): string
    {
        $tahun ??= (int) now()->year;

        return DB::transaction(function () use ($tahun, $seri): string {
            $baris = NomorKuitansiUrutan::query()
                ->where('seri', $seri)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if (! $baris) {
                NomorKuitansiUrutan::query()->create([
                    'seri' => $seri,
                    'tahun' => $tahun,
                    'nomor_terakhir' => 0,
                ]);

                $baris = NomorKuitansiUrutan::query()
                    ->where('seri', $seri)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $nomor = $baris->nomor_terakhir + 1;
            $baris->update(['nomor_terakhir' => $nomor]);

            return sprintf('%s-%s-%04d', $seri, now()->format('Ymd'), $nomor);
        });
    }
}
