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
        try {
            if (class_exists(Siswa::class)) {
                Siswa::sinkronkanDataMasuk(true);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Migration recalculate siswa data: ' . $e->getMessage());
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
