<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_tanggungan_pemerintah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('cakupan', 20)->default('spp'); // spp, pos
            $table->foreignId('spp_id')->nullable()->constrained('spps')->cascadeOnDelete();
            $table->date('mulai');
            $table->timestamp('dicabut_at')->nullable();
            $table->text('alasan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['siswa_id', 'cakupan', 'spp_id']);
            $table->index(['dicabut_at', 'mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_tanggungan_pemerintah');
    }
};
