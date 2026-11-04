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
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('nisn');
            $table->string('tempat_lahir', 100)->nullable()->after('nama');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->string('agama', 20)->nullable()->after('tanggal_lahir');
            $table->date('tanggal_masuk')->nullable()->after('alamat');
            $table->string('status', 20)->default('aktif')->after('tanggal_masuk');
        });

        Schema::table('siswas', function (Blueprint $table) {
            $table->string('nisn', 15)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'tempat_lahir', 'tanggal_lahir', 'agama', 'tanggal_masuk', 'status']);
        });
    }
};
