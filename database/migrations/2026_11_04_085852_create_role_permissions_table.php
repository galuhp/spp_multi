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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('permission', 50);
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });

        $defaults = [
            'admin' => ['dashboard', 'transaksi_spp', 'portal_siswa', 'kesiswaan', 'master_data', 'pengaturan_akses'],
            'petugas' => ['dashboard', 'transaksi_spp', 'portal_siswa', 'kesiswaan', 'master_data'],
            'siswa' => ['dashboard', 'portal_siswa'],
        ];

        $now = now();

        foreach ($defaults as $role => $permissions) {
            foreach ($permissions as $permission) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
