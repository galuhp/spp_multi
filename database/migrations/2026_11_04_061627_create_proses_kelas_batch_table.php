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
        Schema::create('proses_kelas_batch', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20); // naik_kelas, kelulusan
            $table->foreignId('tahun_ajaran_asal')->constrained('tahun_ajarans')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_tujuan')->nullable()->constrained('tahun_ajarans')->restrictOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('jumlah_siswa')->default(0);
            $table->string('status', 20)->default('selesai'); // selesai, dibatalkan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proses_kelas_batch');
    }
};
