<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Master data is an administrator responsibility by default.
     */
    public function up(): void
    {
        DB::table('role_permissions')
            ->where('role', 'petugas')
            ->where('permission', 'master_data')
            ->delete();
    }

    /**
     * Restore the previous default permission when rolling back.
     */
    public function down(): void
    {
        $now = now();

        DB::table('role_permissions')->insertOrIgnore([
            'role' => 'petugas',
            'permission' => 'master_data',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
