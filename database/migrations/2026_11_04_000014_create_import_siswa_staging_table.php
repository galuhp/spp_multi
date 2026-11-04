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
        Schema::create('import_siswa_staging', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_file');
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->foreignId('spp_id')->constrained('spps')->restrictOnDelete();
            $table->unsignedInteger('baris');

            // Nilai mentah dari Excel. Kunci identitas tetap disimpan sebagai string.
            $table->string('nisn', 50)->nullable();
            $table->string('nik', 50)->nullable();
            $table->string('nis', 50)->nullable();
            $table->string('nama_lengkap', 150)->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->string('agama', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('tanggal_masuk', 30)->nullable();
            $table->string('kode_kompetensi', 30)->nullable();
            $table->string('kelas', 100)->nullable();
            $table->string('nama_ayah', 150)->nullable();
            $table->string('nama_ibu', 150)->nullable();
            $table->string('nama_wali', 150)->nullable();
            $table->string('hp_wali', 30)->nullable();

            $table->string('status', 15)->default('valid')->index();
            $table->text('alasan')->nullable();
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
            $table->boolean('ditimpa')->default(false);
            $table->boolean('tersimpan')->default(false);
            $table->timestamps();

            $table->index(['batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_siswa_staging');
    }
};
