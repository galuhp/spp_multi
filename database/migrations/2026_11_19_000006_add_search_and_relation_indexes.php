<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes on tenant_id, relationships, and frequently searched columns.
     */
    public function up(): void
    {
        if (Schema::hasTable('siswas')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->index(['tenant_id', 'nama'], 'idx_siswas_tenant_nama');
                $table->index(['tenant_id', 'kelas_id'], 'idx_siswas_tenant_kelas');
                $table->index(['tenant_id', 'nis'], 'idx_siswas_tenant_nis');
                $table->index(['tenant_id', 'nisn'], 'idx_siswas_tenant_nisn');
            });
        }

        if (Schema::hasTable('pembayarans')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->index(['tenant_id', 'tgl_bayar'], 'idx_pembayarans_tenant_tgl_bayar');
                $table->index(['tenant_id', 'created_at'], 'idx_pembayarans_tenant_created_at');
                $table->index(['tenant_id', 'siswa_id'], 'idx_pembayarans_tenant_siswa');
                $table->index(['tenant_id', 'kode_transaksi'], 'idx_pembayarans_tenant_kode');
            });
        }

        if (Schema::hasTable('kelas')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->index(['tenant_id', 'nama_kelas'], 'idx_kelas_tenant_nama');
                $table->index(['tenant_id', 'jurusan_id'], 'idx_kelas_tenant_jurusan');
            });
        }

        if (Schema::hasTable('jurusans')) {
            Schema::table('jurusans', function (Blueprint $table) {
                $table->index(['tenant_id', 'nama'], 'idx_jurusans_tenant_nama');
            });
        }

        if (Schema::hasTable('spps')) {
            Schema::table('spps', function (Blueprint $table) {
                $table->index(['tenant_id', 'tahun_ajaran'], 'idx_spps_tenant_tahun_ajaran');
            });
        }

        if (Schema::hasTable('pengeluaran_sekolahs')) {
            Schema::table('pengeluaran_sekolahs', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_at'], 'idx_pengeluaran_tenant_created_at');
            });
        }

        if (Schema::hasTable('tenant_invoices')) {
            Schema::table('tenant_invoices', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'idx_tenant_invoices_tenant_status');
                $table->index(['status', 'period'], 'idx_tenant_invoices_status_period');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('name', 'idx_users_name');
                $table->index('role', 'idx_users_role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('siswas')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropIndex('idx_siswas_tenant_nama');
                $table->dropIndex('idx_siswas_tenant_kelas');
                $table->dropIndex('idx_siswas_tenant_nis');
                $table->dropIndex('idx_siswas_tenant_nisn');
            });
        }

        if (Schema::hasTable('pembayarans')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->dropIndex('idx_pembayarans_tenant_tgl_bayar');
                $table->dropIndex('idx_pembayarans_tenant_created_at');
                $table->dropIndex('idx_pembayarans_tenant_siswa');
                $table->dropIndex('idx_pembayarans_tenant_kode');
            });
        }

        if (Schema::hasTable('kelas')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropIndex('idx_kelas_tenant_nama');
                $table->dropIndex('idx_kelas_tenant_jurusan');
            });
        }

        if (Schema::hasTable('jurusans')) {
            Schema::table('jurusans', function (Blueprint $table) {
                $table->dropIndex('idx_jurusans_tenant_nama');
            });
        }

        if (Schema::hasTable('spps')) {
            Schema::table('spps', function (Blueprint $table) {
                $table->dropIndex('idx_spps_tenant_tahun_ajaran');
            });
        }

        if (Schema::hasTable('pengeluaran_sekolahs')) {
            Schema::table('pengeluaran_sekolahs', function (Blueprint $table) {
                $table->dropIndex('idx_pengeluaran_tenant_created_at');
            });
        }

        if (Schema::hasTable('tenant_invoices')) {
            Schema::table('tenant_invoices', function (Blueprint $table) {
                $table->dropIndex('idx_tenant_invoices_tenant_status');
                $table->dropIndex('idx_tenant_invoices_status_period');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_name');
                $table->dropIndex('idx_users_role');
            });
        }
    }
};
