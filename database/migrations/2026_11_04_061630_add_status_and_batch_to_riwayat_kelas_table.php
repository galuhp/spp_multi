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
        Schema::table('riwayat_kelas', function (Blueprint $table) {
            $table->string('status', 20)->default('aktif')->after('tahun_ajaran_id');
            $table->foreignId('batch_id')->nullable()->after('status')
                ->constrained('proses_kelas_batch')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn('status');
        });
    }
};
