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
        Schema::create('profil_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah', 150)->default('SMK Negeri Contoh');
            $table->string('npsn', 30)->nullable()->default('20100001');
            $table->string('jenjang', 20)->nullable()->default('SMK');
            $table->string('pemerintah_daerah', 150)->nullable()->default('PEMERINTAH DAERAH PROVINSI JAWA BARAT');
            $table->string('dinas_pendidikan', 150)->nullable()->default('DINAS PENDIDIKAN DAN KEBUDAYAAN');
            $table->text('alamat')->nullable();
            $table->string('kota', 60)->nullable()->default('Bandung');
            $table->string('kode_pos', 15)->nullable()->default('40123');
            $table->string('no_telp', 40)->nullable()->default('(022) 1234567');
            $table->string('email', 100)->nullable()->default('info@smknegericontoh.sch.id');
            $table->string('website', 120)->nullable()->default('www.smknegericontoh.sch.id');
            $table->string('nama_kepala_sekolah', 120)->nullable()->default('Drs. H. Rahmat Hidayat, M.M.Pd.');
            $table->string('nip_kepala_sekolah', 50)->nullable()->default('19700101 199503 1 001');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_sekolahs');
    }
};
