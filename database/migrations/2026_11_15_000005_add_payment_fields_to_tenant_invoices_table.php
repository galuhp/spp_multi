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
        Schema::table('tenant_invoices', function (Blueprint $table) {
            $table->string('doku_invoice_number')->nullable()->unique()->after('status');
            $table->text('payment_url')->nullable()->after('doku_invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_invoices', function (Blueprint $table) {
            $table->dropColumn(['doku_invoice_number', 'payment_url']);
        });
    }
};
