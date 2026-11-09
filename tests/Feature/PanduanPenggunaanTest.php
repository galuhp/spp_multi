<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\PanduanPenggunaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanduanPenggunaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_tidak_bisa_unduh_panduan(): void
    {
        $response = $this->get(route('panduan.unduh'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_login_dapat_mengunduh_berkas_panduan_markdown(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Petugas,
        ]);

        $response = $this->actingAs($user)->get(route('panduan.unduh'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Buku-Panduan-E-SPP.md');
        $this->assertStringContainsString('BUKU PANDUAN PENGGUNAAN SISTEM INFORMASI SPP', $response->streamedContent());
    }

    public function test_halaman_filament_panduan_penggunaan_dapat_diakses_dan_merender_bab(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($user);

        Livewire::test(PanduanPenggunaan::class)
            ->assertOk()
            ->assertSee('Buku Panduan Penggunaan & SOP Operasional')
            ->assertSee('BAB 1: Pengenalan & Hak Akses Pengguna')
            ->assertSee('BAB 3: Transaksi Kasir & Penerimaan Keuangan')
            ->assertSee('BAB 6: Siklus Akhir Tahun Ajaran (Kelulusan & Kenaikan Kelas)')
            ->assertSee('BAB 9: Tanya Jawab (FAQ) & Solusi Permasalahan')
            ->assertSeeHtml('id="bab-1-pengenalan--hak-akses-pengguna"')
            ->assertSeeHtml('href="#bab-3-transaksi-kasir--penerimaan-keuangan"')
            ->assertSeeHtml('panduan-alert panduan-alert-important')
            ->assertSee('Penting')
            ->assertDontSee('[!IMPORTANT]');
    }
}
