<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantBillingGracePeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_invoice_before_due_date_does_not_lock_tenant_panel(): void
    {
        $tenant = Tenant::create(['name' => 'SMK Grace', 'slug' => 'smk-grace', 'is_active' => true]);
        $admin = User::factory()->create(['is_super_admin' => false, 'role' => UserRole::Admin]);
        $tenant->users()->attach($admin->id, ['role' => UserRole::Admin->value]);

        TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'period' => now()->subMonth()->format('Y-m'),
            'transaction_count' => 10,
            'fee_per_transaction' => 1000,
            'total_amount' => 10000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $this->assertTrue($tenant->hasUnpaidInvoices());
        $this->assertFalse($tenant->hasOverdueInvoices());

        $this->actingAs($admin)
            ->get("/admin/{$tenant->slug}")
            ->assertOk();
    }

    public function test_overdue_invoice_past_due_date_redirects_to_tenant_billing(): void
    {
        $tenant = Tenant::create(['name' => 'SMK Overdue', 'slug' => 'smk-overdue', 'is_active' => true]);
        $admin = User::factory()->create(['is_super_admin' => false, 'role' => UserRole::Admin]);
        $tenant->users()->attach($admin->id, ['role' => UserRole::Admin->value]);

        TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'period' => now()->subMonth()->format('Y-m'),
            'transaction_count' => 10,
            'fee_per_transaction' => 1000,
            'total_amount' => 10000,
            'status' => 'unpaid',
            'due_date' => now()->subDay(),
        ]);

        $this->assertTrue($tenant->hasOverdueInvoices());

        $this->actingAs($admin)
            ->get("/admin/{$tenant->slug}")
            ->assertRedirect("/admin/{$tenant->slug}/tenant-billing");
    }

    public function test_generate_invoices_command_does_not_overwrite_paid_invoice(): void
    {
        $tenant = Tenant::create([
            'name' => 'SMK Lunas',
            'slug' => 'smk-lunas',
            'is_active' => true,
            'fee_per_transaction' => 1000,
        ]);

        $period = now()->subMonth()->format('Y-m');
        $paidAt = now()->subDays(2);

        $invoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'period' => $period,
            'transaction_count' => 5,
            'fee_per_transaction' => 1000,
            'total_amount' => 5000,
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);

        Artisan::call('tenants:generate-invoices', ['--period' => $period]);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(5000, $invoice->total_amount);
    }
}
