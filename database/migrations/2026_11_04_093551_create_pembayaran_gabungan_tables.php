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
        Schema::create('pembayaran_gabungan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->date('tanggal');
            $table->string('sumber', 30); // setoran_kelas, transfer_bank, catatan_manual
            $table->string('metode', 20); // tunai, transfer
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->unsignedBigInteger('total_diterima')->default(0);
            $table->string('status', 20)->default('draft'); // draft, memproses, diposting, void
            $table->text('catatan')->nullable();
            $table->string('idempotency_token', 64)->unique();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('diposting_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diposting_at')->nullable();
            $table->foreignId('void_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_alasan')->nullable();
            $table->timestamp('void_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran_gabungan_baris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gabungan_id')->constrained('pembayaran_gabungan')->cascadeOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->restrictOnDelete();
            $table->unsignedBigInteger('jumlah');
            $table->string('referensi')->nullable();
            $table->json('alokasi')->nullable();
            $table->string('status_validasi', 20)->default('ok'); // ok, error, peringatan
            $table->text('pesan')->nullable();
            $table->foreignId('pembayaran_id')->nullable()->constrained('pembayarans')->nullOnDelete();
            $table->foreignId('void_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_alasan')->nullable();
            $table->timestamp('void_at')->nullable();
            $table->timestamps();

            $table->index(['gabungan_id', 'siswa_id']);
        });

        Schema::create('nomor_kuitansi_urutan', function (Blueprint $table) {
            $table->id();
            $table->string('seri', 20)->default('SPP');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedBigInteger('nomor_terakhir')->default(0);
            $table->timestamps();

            $table->unique(['seri', 'tahun']);
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->foreignId('gabungan_id')->nullable()->after('catatan')
                ->constrained('pembayaran_gabungan')->nullOnDelete();
            $table->foreignId('gabungan_baris_id')->nullable()->after('gabungan_id')
                ->constrained('pembayaran_gabungan_baris')->nullOnDelete();
            $table->foreignId('void_oleh')->nullable()->after('gabungan_baris_id')
                ->constrained('users')->nullOnDelete();
            $table->text('void_alasan')->nullable()->after('void_oleh');
            $table->timestamp('void_at')->nullable()->after('void_alasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gabungan_baris_id');
            $table->dropConstrainedForeignId('gabungan_id');
            $table->dropConstrainedForeignId('void_oleh');
            $table->dropColumn(['void_alasan', 'void_at']);
        });

        Schema::dropIfExists('nomor_kuitansi_urutan');
        Schema::dropIfExists('pembayaran_gabungan_baris');
        Schema::dropIfExists('pembayaran_gabungan');
    }
};
