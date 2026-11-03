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
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50)->index();
            $table->string('subjek_tipe', 50)->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->string('deskripsi');
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subjek_tipe', 'subjek_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
