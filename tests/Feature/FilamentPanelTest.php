<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ProfilSekolahSettings;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_admin(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/admin');
    }

    public function test_filament_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Username / NISN');
        $response->assertDontSee('Akses Akun Demo Cepat');
    }

    public function test_user_can_login_to_filament_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'admin_test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'admin_test',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_error_on_username_field(): void
    {
        User::factory()->create([
            'username' => 'admin_test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'admin_test',
                'password' => 'password-salah',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username'])
            ->assertNotified('Login gagal')
            ->assertSee('Username atau kata sandi tidak sesuai.');

        $this->assertGuest();
    }

    public function test_admin_can_access_dashboard_and_all_resources(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSee('E-SPP SEKOLAH');

        $this->actingAs($admin)
            ->get('/admin/jurusans')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/kelas')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/spps')
            ->assertStatus(200)
            ->assertDontSee('Reset & Seed Data Demo');

        $this->actingAs($admin)
            ->get('/admin/siswas')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/pembayarans')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/log-aktivitas')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/profil-sekolah-settings')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/laporan-pembayaran')
            ->assertStatus(200);
    }

    public function test_petugas_can_access_operational_but_not_master_data_or_user_management(): void
    {
        $petugas = User::factory()->create([
            'role' => UserRole::Petugas,
        ]);

        $this->actingAs($petugas)
            ->get('/admin')
            ->assertStatus(200);

        $this->actingAs($petugas)
            ->get('/admin/siswas')
            ->assertStatus(200);

        $this->actingAs($petugas)
            ->get('/admin/pembayarans')
            ->assertStatus(200);

        $this->actingAs($petugas)
            ->get('/admin/jurusans')
            ->assertStatus(403);

        $this->actingAs($petugas)
            ->get('/admin/kelas')
            ->assertStatus(403);

        $this->actingAs($petugas)
            ->get('/admin/spps')
            ->assertStatus(403);

        $this->actingAs($petugas)
            ->get('/admin/tahun-ajarans')
            ->assertStatus(403);

        $this->actingAs($petugas)
            ->get('/admin/users')
            ->assertStatus(403);
    }

    public function test_siswa_can_access_tagihan_page(): void
    {
        $jurusan = Jurusan::create(['nama' => 'RPL', 'kode' => 'RPL']);
        $kelas = Kelas::create(['nama_kelas' => 'XII RPL 1', 'tingkat' => 'XII', 'jurusan_id' => $jurusan->id]);
        $spp = Spp::create(['kategori' => Spp::KATEGORI_SPP, 'nama_tarif' => 'SPP', 'tahun' => 2026, 'tahun_ajaran' => '2026/2027', 'nominal' => 250000]);

        $siswaUser = User::factory()->create([
            'username' => '0012345678',
            'role' => UserRole::Siswa,
        ]);

        Siswa::create([
            'nisn' => '0012345678',
            'nis' => '12345',
            'nama' => 'Budi Siswa',
            'kelas_id' => $kelas->id,
            'spp_id' => $spp->id,
            'user_id' => $siswaUser->id,
        ]);

        $this->actingAs($siswaUser)
            ->get('/admin/tagihan-siswa')
            ->assertStatus(200)
            ->assertSee('Budi Siswa');
    }

    public function test_admin_can_save_school_profile_settings(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        ProfilSekolah::current();

        $this->actingAs($admin);

        Livewire::test(ProfilSekolahSettings::class)
            ->set('data.nama_sekolah', 'SMK Negeri 99 Bandung')
            ->set('data.npsn', '99887766')
            ->set('data.jenjang', 'SMK')
            ->set('data.pemerintah_daerah', 'PEMERINTAH PROVINSI JAWA BARAT')
            ->set('data.dinas_pendidikan', 'DINAS PENDIDIKAN')
            ->set('data.alamat', 'Jl. Sukajadi No. 99')
            ->set('data.kota', 'Bandung')
            ->set('data.nama_kepala_sekolah', 'Dr. Budi Santoso, M.Pd.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolahs', [
            'nama_sekolah' => 'SMK Negeri 99 Bandung',
            'npsn' => '99887766',
        ]);
    }

    public function test_admin_can_save_app_name_and_school_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        ProfilSekolah::current();

        $this->actingAs($admin);

        Livewire::test(ProfilSekolahSettings::class)
            ->set('data.app_name', 'SIPENA SEKOLAH')
            ->set('data.logo', UploadedFile::fake()->image('logo-sekolah.png', 200, 200))
            ->call('save')
            ->assertHasNoErrors();

        $profil = ProfilSekolah::current();

        $this->assertSame('SIPENA SEKOLAH', $profil->app_name);
        $this->assertNotNull($profil->logo);
        Storage::disk('public')->assertExists($profil->logo);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('SIPENA SEKOLAH')
            ->assertSee($profil->logoUrl())
            ->assertSee('<link rel="icon" href="'.$profil->logoUrl().'"', false)
            ->assertSee('Profil Saya')
            ->assertSee('Keluar');
    }
}
