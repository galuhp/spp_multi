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
        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('period', 7); // Format: YYYY-MM
            $table->unsignedInteger('transaction_count')->default(0);
            $table->unsignedBigInteger('fee_per_transaction')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status', 20)->default('unpaid'); // unpaid, paid, overdue
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_invoices');
    }
};
