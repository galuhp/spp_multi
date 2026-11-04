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
        Schema::table('spps', function (Blueprint $table) {
            $table->string('kategori', 50)->default('SPP')->after('id');
            $table->string('tipe', 30)->default('bulanan')->after('kategori');
            $table->string('nama', 100)->nullable()->after('tipe');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropUnique('unique_siswa_spp_bulan_tahun');
            $table->string('bulan_dibayar', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('bulan_dibayar', 20)->nullable(false)->change();
            $table->unique(['siswa_id', 'bulan_dibayar', 'tahun_dibayar'], 'unique_siswa_spp_bulan_tahun');
        });

        Schema::table('spps', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'tipe', 'nama']);
        });
    }
};
