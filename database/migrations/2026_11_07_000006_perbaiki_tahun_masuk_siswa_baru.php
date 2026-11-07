<?php

use App\Models\Siswa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (class_exists(Siswa::class)) {
                Siswa::sinkronkanDataMasuk(true);
            }
        } catch (Throwable $e) {
            Log::warning('Migration perbaiki tahun masuk siswa: '.$e->getMessage());
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
