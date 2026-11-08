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
        Schema::create('siswa_subsidies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('cakupan', 20)->default('spp'); // spp, pos, semua
            $table->foreignId('spp_id')->nullable()->constrained('spps')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->unsignedTinyInteger('persentase'); // 1 - 100
            $table->string('jenis', 50)->default('lainnya'); // anak_guru, prestasi, afirmasi, yayasan, lainnya
            $table->string('nomor_sk', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->date('mulai')->nullable();
            $table->date('selesai')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['siswa_id', 'is_aktif']);
            $table->index(['siswa_id', 'cakupan', 'spp_id']);
            $table->index(['tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa_subsidies');
    }
};
