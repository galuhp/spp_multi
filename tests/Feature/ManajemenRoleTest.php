<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\LaporanPembayaran;
use App\Filament\Pages\LaporanTunggakan;
use App\Filament\Pages\ManajemenRole;
use App\Filament\Pages\RekapPotensiPenerimaan;
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

    public function test_admin_dapat_mengubah_matriks_akses_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($admin);

        Livewire::test(ManajemenRole::class)
            ->set('akses.petugas.kesiswaan', false)
            ->call('simpanAkses')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'petugas',
            'permission' => 'kesiswaan',
        ]);

        $this->actingAs($petugas)
            ->get('/admin/siswas')
            ->assertForbidden();
    }

    public function test_hak_akses_laporan_mengatur_halaman_laporan_staff(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($admin);

        Livewire::test(ManajemenRole::class)
            ->set('akses.petugas.laporan', false)
            ->call('simpanAkses')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'petugas',
            'permission' => 'laporan',
        ]);

        foreach ([LaporanPembayaran::class, LaporanTunggakan::class, RekapPotensiPenerimaan::class] as $halaman) {
            $this->actingAs($petugas)
                ->get($halaman::getUrl())
                ->assertForbidden();
        }

        foreach (['laporan.cetak', 'laporan.tunggakan.cetak', 'laporan.potensi.cetak'] as $rute) {
            $this->actingAs($petugas)
                ->get(route($rute))
                ->assertForbidden();
        }
    }
}
