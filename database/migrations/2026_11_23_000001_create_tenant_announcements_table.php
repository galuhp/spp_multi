<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('message');
            $table->string('type', 20)->default('info');
            $table->string('target_type', 20)->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'target_type', 'starts_at', 'ends_at'], 'tenant_announcements_active_idx');
        });

        Schema::create('tenant_announcement_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_announcement_id')->constrained('tenant_announcements')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->unique(['tenant_announcement_id', 'tenant_id'], 'tenant_announcement_tenant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_announcement_tenant');
        Schema::dropIfExists('tenant_announcements');
    }
};
