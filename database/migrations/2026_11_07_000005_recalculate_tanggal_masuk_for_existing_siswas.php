<?php

use App\Models\Siswa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hitung ulang dan sinkronkan tanggal_masuk dan tahun_masuk untuk semua siswa yang sudah ada
        if (class_exists(Siswa::class)) {
            Siswa::sinkronkanDataMasuk(true);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
