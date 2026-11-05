<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One checkout may create several payment rows (SPP months or mixed tariffs)
     * that share the same receipt number.
     */
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropUnique('pembayarans_kode_transaksi_unique');
            $table->index('kode_transaksi', 'pembayarans_kode_transaksi_index');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('pembayarans_kode_transaksi_index');
            $table->unique('kode_transaksi', 'pembayarans_kode_transaksi_unique');
        });
    }
};
