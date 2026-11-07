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
        Schema::create('pengeluaran_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi', 40)->unique();
            $table->date('tanggal');
            $table->string('kategori', 50); // Operasional, Gaji & Honor, ATK & Cetak, Sarana & Prasarana, Kegiatan Siswa, Utilitas, Pemeliharaan, Lainnya
            $table->string('keperluan', 255);
            $table->unsignedInteger('nominal');
            $table->string('penerima', 100)->nullable();
            $table->string('metode_pembayaran', 30)->default('Tunai'); // Tunai, Transfer Bank
            $table->text('catatan')->nullable();
            $table->string('bukti_transaksi', 255)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'kategori']);
            $table->index('tahun_ajaran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_sekolahs');
    }
};
