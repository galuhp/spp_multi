<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-scoped tables.
     *
     * @var list<string>
     */
    protected array $tables = [
        'profil_sekolahs',
        'tahun_ajarans',
        'jurusans',
        'kelas',
        'siswas',
        'walis',
        'spps',
        'pembayarans',
        'pembayaran_gabungans',
        'pembayaran_gabungan_baris',
        'pengeluaran_sekolahs',
        'siswa_subsidis',
        'siswa_tanggungan_pemerintahs',
        'riwayat_kelas',
        'proses_kelas_batches',
        'import_siswa_stagings',
        'nomor_kuitansi_urutans',
        'log_aktivitas',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
