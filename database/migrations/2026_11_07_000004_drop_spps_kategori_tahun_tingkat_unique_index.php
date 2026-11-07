<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('spps', function (Blueprint $table) {
                // Drop unique index on (kategori, tahun_ajaran, tingkat) because multiple items
                // in the same category (e.g. PTS Ganjil, PTS Genap, PAS Ganjil, PAS Genap under 'Biaya Ujian')
                // exist for the same tingkat and tahun_ajaran.
                $table->dropUnique('spps_kategori_tahun_tingkat_unique');
            });
        } catch (\Throwable $e) {
            // Index might already be dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('spps', function (Blueprint $table) {
                $table->unique(['kategori', 'tahun_ajaran', 'tingkat'], 'spps_kategori_tahun_tingkat_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if unable to re-add
        }
    }
};
