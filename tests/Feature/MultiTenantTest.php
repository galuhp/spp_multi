<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_tenant_scope_isolates_records_between_tenants(): void
    {
        $tenantA = Tenant::create(['name' => 'SMK Negeri 1', 'slug' => 'smkn-1', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'SMK Negeri 2', 'slug' => 'smkn-2', 'is_active' => true]);

        TenantContext::set($tenantA);
        $jurusanA = Jurusan::create(['nama_jurusan' => 'RPL', 'kode_jurusan' => 'RPL']);

        TenantContext::set($tenantB);
        $jurusanB = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ']);

        $this->assertSame($tenantA->id, $jurusanA->tenant_id);
        $this->assertSame($tenantB->id, $jurusanB->tenant_id);

        TenantContext::set($tenantA);
        $this->assertCount(1, Jurusan::all());
        $this->assertSame('RPL', Jurusan::first()->nama_jurusan);

        TenantContext::set($tenantB);
        $this->assertCount(1, Jurusan::all());
        $this->assertSame('TKJ', Jurusan::first()->nama_jurusan);
    }

    public function test_user_can_only_access_assigned_active_tenants_unless_super_admin(): void
    {
        $tenantA = Tenant::create(['name' => 'SMK Negeri 1', 'slug' => 'smkn-1', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'SMK Negeri 2', 'slug' => 'smkn-2', 'is_active' => true]);
        $inactiveTenant = Tenant::create(['name' => 'SMK Nonaktif', 'slug' => 'smk-off', 'is_active' => false]);

        $regularUser = User::factory()->create(['is_super_admin' => false]);
        $regularUser->tenants()->attach([$tenantA->id, $inactiveTenant->id]);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $panel = Filament::getPanel('admin');

        $this->assertTrue($regularUser->canAccessTenant($tenantA));
        $this->assertFalse($regularUser->canAccessTenant($tenantB));
        $this->assertFalse($regularUser->canAccessTenant($inactiveTenant));
        $this->assertCount(1, $regularUser->getTenants($panel));

        $this->assertTrue($superAdmin->canAccessTenant($tenantA));
        $this->assertTrue($superAdmin->canAccessTenant($tenantB));
        $this->assertCount(2, $superAdmin->getTenants($panel));
    }
}
