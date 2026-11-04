<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\TahunAjarans\Pages\CreateTahunAjaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TahunAjaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengaktifkan_tahun_ajaran_menonaktifkan_yang_lain(): void
    {
        $lama = TahunAjaran::query()->create([
            'nama' => '2024/2025',
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30',
            'aktif' => true,
        ]);

        $baru = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'aktif' => true,
        ]);

        $this->assertFalse($lama->fresh()->aktif);
        $this->assertTrue($baru->fresh()->aktif);
        $this->assertSame(1, TahunAjaran::query()->where('aktif', true)->count());
    }

    public function test_admin_dapat_menambah_tahun_ajaran(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin);

        Livewire::test(CreateTahunAjaran::class)
            ->fillForm([
                'nama' => '2027/2028',
                'tanggal_mulai' => '2027-07-01',
                'tanggal_selesai' => '2028-06-30',
                'aktif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tahun_ajarans', [
            'nama' => '2027/2028',
            'aktif' => true,
        ]);
    }

    public function test_format_nama_tahun_ajaran_tidak_valid_ditolak(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin);

        Livewire::test(CreateTahunAjaran::class)
            ->fillForm([
                'nama' => '2027-2028',
                'tanggal_mulai' => '2027-07-01',
                'tanggal_selesai' => '2028-06-30',
                'aktif' => false,
            ])
            ->call('create')
            ->assertHasFormErrors(['nama']);

        $this->assertDatabaseMissing('tahun_ajarans', ['nama' => '2027-2028']);
    }

    public function test_admin_dapat_membuka_halaman_ubah_tahun_ajaran(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2028/2029',
            'tanggal_mulai' => '2028-07-01',
            'tanggal_selesai' => '2029-06-30',
            'aktif' => false,
        ]);

        $this->actingAs($admin)
            ->get("/admin/tahun-ajarans/{$tahunAjaran->id}/edit")
            ->assertOk();
    }
}
