<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('kelas') && ! Schema::hasColumn('kelas', 'wali_kelas_id')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->foreignId('wali_kelas_id')
                    ->nullable()
                    ->after('tahun_ajaran_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('role_permissions')) {
            $now = now();
            DB::table('role_permissions')->insertOrIgnore([
                [
                    'role' => 'wali_kelas',
                    'permission' => 'dashboard',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'role' => 'wali_kelas',
                    'permission' => 'laporan_tunggakan',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role', 'wali_kelas')
                ->delete();
        }

        if (Schema::hasTable('kelas') && Schema::hasColumn('kelas', 'wali_kelas_id')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('wali_kelas_id');
            });
        }
    }
};
