<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\SuperAdmin\Resources\TenantResource\Pages\CreateTenant;
use App\Models\TahunAjaran;
use App\Models\Tenant;
use App\Models\TenantAnnouncement;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_onboarding_creates_tenant_initial_admin_and_active_tahun_ajaran(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        Livewire::test(CreateTenant::class)
            ->fillForm([
                'name' => 'SMK Harapan Bangsa',
                'slug' => 'smk-harapan-bangsa',
                'fee_per_transaction' => 1500,
                'is_active' => true,
                'create_initial_admin' => true,
                'admin_name' => 'Admin Harapan',
                'admin_username' => 'admin_harapan',
                'admin_email' => 'admin@harapan.sch.id',
                'admin_password' => 'password123',
                'create_initial_tahun_ajaran' => true,
                'tahun_ajaran_nama' => '2026/2027',
                'tahun_ajaran_mulai' => '2026-07-01',
                'tahun_ajaran_selesai' => '2027-06-30',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tenant = Tenant::query()->where('slug', 'smk-harapan-bangsa')->firstOrFail();

        $admin = User::query()->where('username', 'admin_harapan')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($tenant->users()->whereKey($admin->id)->exists());

        TenantContext::set($tenant);
        $tahunAjaran = TahunAjaran::query()->where('nama', '2026/2027')->first();
        $this->assertNotNull($tahunAjaran);
        $this->assertTrue($tahunAjaran->aktif);
    }

    public function test_leave_impersonation_restores_super_admin_session(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'SMK Negeri 1', 'slug' => 'smkn-1', 'is_active' => true]);
        $schoolAdmin = User::factory()->create([
            'is_super_admin' => false,
            'role' => UserRole::Admin,
        ]);
        $tenant->users()->attach($schoolAdmin->id, ['role' => UserRole::Admin->value]);

        $response = $this->actingAs($schoolAdmin)
            ->withSession([
                'impersonator_id' => $superAdmin->id,
                'impersonator_tenant_id' => $tenant->id,
                'impersonator_return_url' => '/super-admin/tenants',
            ])
            ->post(route('impersonate.leave'));

        $response->assertRedirect('/super-admin/tenants');
        $this->assertAuthenticatedAs($superAdmin);
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_leave_impersonation_rejects_request_without_impersonator_session(): void
    {
        $schoolAdmin = User::factory()->create([
            'is_super_admin' => false,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($schoolAdmin)
            ->post(route('impersonate.leave'))
            ->assertForbidden();
    }

    public function test_tenant_announcement_scope_filters_target_and_schedule(): void
    {
        $tenantA = Tenant::create(['name' => 'SMK A', 'slug' => 'smk-a', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'SMK B', 'slug' => 'smk-b', 'is_active' => true]);

        $globalActive = TenantAnnouncement::create([
            'title' => 'Maintenance Terjadwal',
            'message' => 'Sistem akan maintenance malam ini.',
            'type' => 'warning',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $targetedA = TenantAnnouncement::create([
            'title' => 'Khusus SMK A',
            'message' => 'Tagihan bulan ini telah terbit.',
            'type' => 'info',
            'target_type' => 'selected',
            'is_active' => true,
        ]);
        $targetedA->tenants()->attach($tenantA->id);

        $inactive = TenantAnnouncement::create([
            'title' => 'Nonaktif',
            'message' => 'Tidak boleh tampil.',
            'type' => 'info',
            'target_type' => 'all',
            'is_active' => false,
        ]);

        $expired = TenantAnnouncement::create([
            'title' => 'Kedaluwarsa',
            'message' => 'Sudah lewat.',
            'type' => 'info',
            'target_type' => 'all',
            'is_active' => true,
            'ends_at' => now()->subMinute(),
        ]);

        $forA = TenantAnnouncement::query()->activeForTenant($tenantA)->pluck('id')->all();
        $forB = TenantAnnouncement::query()->activeForTenant($tenantB)->pluck('id')->all();

        $this->assertContains($globalActive->id, $forA);
        $this->assertContains($targetedA->id, $forA);
        $this->assertNotContains($inactive->id, $forA);
        $this->assertNotContains($expired->id, $forA);

        $this->assertContains($globalActive->id, $forB);
        $this->assertNotContains($targetedA->id, $forB);
    }
}
