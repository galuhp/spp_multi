<?php

namespace Tests\Feature;

use App\Enums\AppTier;
use App\Enums\UserRole;
use App\Filament\SuperAdmin\Pages\SystemHealthMonitor;
use App\Filament\SuperAdmin\Widgets\InactiveTenantsWidget;
use App\Filament\SuperAdmin\Widgets\SaaSRevenueChartWidget;
use App\Models\StudentPaymentOrder;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\User;
use App\Support\LisensiPaket;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_lisensi_paket_resolves_per_tenant_tier_before_global_config(): void
    {
        config(['spp.tier' => 'basic']);

        $proTenant = Tenant::create([
            'name' => 'SMK Pro',
            'slug' => 'smk-pro',
            'is_active' => true,
            'settings' => ['tier' => 'pro', 'max_siswa' => 500],
        ]);

        $defaultTenant = Tenant::create([
            'name' => 'SMK Default',
            'slug' => 'smk-default',
            'is_active' => true,
            'settings' => [],
        ]);

        TenantContext::set($proTenant);
        $this->assertSame(AppTier::Pro, LisensiPaket::tier());
        $this->assertTrue(LisensiPaket::isPro());
        $this->assertSame(500, $proTenant->maxSiswa());

        TenantContext::set($defaultTenant);
        $this->assertSame(AppTier::Basic, LisensiPaket::tier());
        $this->assertTrue(LisensiPaket::isBasic());
        $this->assertNull($defaultTenant->maxSiswa());
    }

    public function test_saas_revenue_chart_and_inactive_tenants_widgets_render(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        $tenant = Tenant::create(['name' => 'SMK Pasif', 'slug' => 'smk-pasif', 'is_active' => true]);
        TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'period' => now()->format('Y-m'),
            'transaction_count' => 10,
            'fee_per_transaction' => 1000,
            'total_amount' => 10000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Livewire::test(SaaSRevenueChartWidget::class)
            ->assertSuccessful();

        Livewire::test(InactiveTenantsWidget::class)
            ->assertCanSeeTableRecords([$tenant]);
    }

    public function test_system_health_monitor_shows_problem_orders_only_to_super_admin(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $regularAdmin = User::factory()->create(['is_super_admin' => false, 'role' => UserRole::Admin]);
        $tenant = Tenant::create(['name' => 'SMK Monitor', 'slug' => 'smk-monitor', 'is_active' => true]);

        StudentPaymentOrder::create([
            'tenant_id' => $tenant->id,
            'siswa_id' => 1,
            'order_number' => 'ORD-FAIL-001',
            'provider' => 'midtrans',
            'total_amount' => 150000,
            'status' => StudentPaymentOrder::STATUS_FAILED,
        ]);

        $this->actingAs($regularAdmin)
            ->get('/super-admin/system-health-monitor')
            ->assertForbidden();

        $this->actingAs($superAdmin);

        Livewire::test(SystemHealthMonitor::class)
            ->assertSuccessful()
            ->assertSee('ORD-FAIL-001');
    }
}
