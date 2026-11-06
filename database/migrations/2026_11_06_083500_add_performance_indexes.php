<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes to add per table: index_name => columns.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const INDEXES = [
        'pembayarans' => [
            'pembayarans_siswa_spp_void_idx' => ['siswa_id', 'spp_id', 'void_at'],
            'pembayarans_void_tgl_bayar_idx' => ['void_at', 'tgl_bayar'],
            'pembayarans_siswa_void_tgl_idx' => ['siswa_id', 'void_at', 'tgl_bayar'],
        ],
        'siswas' => [
            'siswas_kelas_archived_status_idx' => ['kelas_id', 'is_archived', 'status'],
            'siswas_nama_idx' => ['nama'],
        ],
        'kelas' => [
            'kelas_tahun_ajaran_tingkat_idx' => ['tahun_ajaran_id', 'tingkat'],
        ],
        'spps' => [
            'spps_kategori_tahun_idx' => ['kategori', 'tahun'],
        ],
        'tahun_ajarans' => [
            'tahun_ajarans_aktif_idx' => ['aktif'],
        ],
        'riwayat_kelas' => [
            'riwayat_kelas_ta_kelas_idx' => ['tahun_ajaran_id', 'kelas_id'],
        ],
        'pembayaran_gabungan' => [
            'pembayaran_gabungan_status_tgl_idx' => ['status', 'tanggal'],
        ],
        'log_aktivitas' => [
            'log_aktivitas_created_at_idx' => ['created_at'],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach ($indexes as $name => $columns) {
                if (in_array($name, $existing, true)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach (array_keys($indexes) as $name) {
                if (! in_array($name, $existing, true)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }
};
