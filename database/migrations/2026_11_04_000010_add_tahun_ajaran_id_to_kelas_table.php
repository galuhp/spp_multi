<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->after('jurusan_id')
                ->constrained('tahun_ajarans')
                ->nullOnDelete();
        });

        $sekarang = now();

        $tahunAjaranId = DB::table('tahun_ajarans')->insertGetId([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ]);

        DB::table('kelas')
            ->whereNull('tahun_ajaran_id')
            ->update(['tahun_ajaran_id' => $tahunAjaranId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tahun_ajaran_id');
        });
    }
};
