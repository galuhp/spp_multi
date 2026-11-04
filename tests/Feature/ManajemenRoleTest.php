<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ManajemenRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManajemenRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_membuka_manajemen_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(ManajemenRole::getUrl())
            ->assertOk()
            ->assertSee('Manajemen Role');
    }

    public function test_petugas_tidak_dapat_membuka_manajemen_role(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($petugas)
            ->get(ManajemenRole::getUrl())
            ->assertForbidden();
    }

    public function test_admin_dapat_mengubah_role_pengguna_lain_dan_tercatat_di_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $pengguna = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($admin);

        Livewire::test(ManajemenRole::class)
            ->call('ubahRole', $pengguna->id, UserRole::Siswa->value)
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Siswa, $pengguna->fresh()->role);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'user.role_diubah',
            'subjek_id' => $pengguna->id,
        ]);
    }

    public function test_admin_tidak_dapat_mengubah_role_sendiri(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin);

        Livewire::test(ManajemenRole::class)
            ->call('ubahRole', $admin->id, UserRole::Petugas->value)
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }
}
