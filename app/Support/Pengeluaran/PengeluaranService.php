<?php

namespace App\Support\Pengeluaran;

use App\Models\LogAktivitas;
use App\Models\PengeluaranSekolah;
use Illuminate\Support\Facades\DB;

class PengeluaranService
{
    /**
     * Cancel an expenditure while preserving the record for auditing.
     */
    public function batalkan(PengeluaranSekolah $pengeluaran, int $userId, string $alasan): PengeluaranSekolah
    {
        return DB::transaction(function () use ($pengeluaran, $userId, $alasan): PengeluaranSekolah {
            $terkunci = PengeluaranSekolah::query()
                ->whereKey($pengeluaran->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($terkunci->isVoid()) {
                return $terkunci;
            }

            $terkunci->update([
                'void_at' => now(),
                'void_oleh' => $userId,
                'void_alasan' => $alasan,
            ]);

            LogAktivitas::catat(
                'pengeluaran.void',
                "Pengeluaran {$terkunci->nomor_transaksi} dibatalkan. Alasan: {$alasan}",
                $terkunci,
                [
                    'nomor_transaksi' => $terkunci->nomor_transaksi,
                    'kategori' => $terkunci->kategori,
                    'nominal' => $terkunci->nominal,
                    'alasan' => $alasan,
                ]
            );

            return $terkunci;
        });
    }
}
