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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('spp_id')->constrained('spps')->restrictOnDelete();
            $table->date('tgl_bayar');
            $table->string('bulan_dibayar', 20); // Januari .. Desember
            $table->unsignedSmallInteger('tahun_dibayar'); // 2026
            $table->unsignedInteger('jumlah_bayar');
            $table->string('metode_pembayaran', 30)->default('Tunai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'bulan_dibayar', 'tahun_dibayar'], 'unique_siswa_spp_bulan_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
