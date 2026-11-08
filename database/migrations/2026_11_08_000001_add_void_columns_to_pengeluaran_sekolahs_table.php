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
        Schema::table('pengeluaran_sekolahs', function (Blueprint $table) {
            $table->timestamp('void_at')->nullable()->after('tahun_ajaran_id');
            $table->foreignId('void_oleh')->nullable()->after('void_at')->constrained('users')->nullOnDelete();
            $table->text('void_alasan')->nullable()->after('void_oleh');

            $table->index(['void_at', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluaran_sekolahs', function (Blueprint $table) {
            $table->dropIndex(['void_at', 'tanggal']);
            $table->dropConstrainedForeignId('void_oleh');
            $table->dropColumn(['void_at', 'void_alasan']);
        });
    }
};
