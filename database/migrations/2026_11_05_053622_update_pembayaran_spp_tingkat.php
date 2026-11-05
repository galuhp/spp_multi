<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Point historical SPP payments at the per-level tariff row.
     */
    public function up(): void
    {
        $pembayarans = DB::table('pembayarans')
            ->join('siswas', 'siswas.id', '=', 'pembayarans.siswa_id')
            ->join('spps as tarif_lama', 'tarif_lama.id', '=', 'pembayarans.spp_id')
            ->join('spps as tarif_baru', 'tarif_baru.id', '=', 'siswas.spp_id')
            ->where('tarif_lama.kategori', 'SPP')
            ->whereNull('tarif_lama.tingkat')
            ->whereNotNull('tarif_baru.tingkat')
            ->whereColumn('tarif_lama.tahun_ajaran', 'tarif_baru.tahun_ajaran')
            ->whereColumn('tarif_lama.id', '!=', 'tarif_baru.id')
            ->select('pembayarans.id', 'tarif_baru.id as spp_id_baru')
            ->get();

        foreach ($pembayarans as $pembayaran) {
            DB::table('pembayarans')
                ->where('id', $pembayaran->id)
                ->update(['spp_id' => $pembayaran->spp_id_baru]);
        }
    }

    /**
     * Data correction is intentionally not reversed.
     */
    public function down(): void
    {
        //
    }
};
