<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Bambang Sudirman',
            'username' => 'admin_utama',
            'email' => 'bambang@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => UserRole::Admin,
        ]);

        $this->petugas = User::factory()->create([
            'name' => 'Siti Khadijah',
            'username' => 'petugas_kasir',
            'email' => 'siti@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => UserRole::Petugas,
        ]);
    }

    public function test_guest_is_redirected_when_accessing_profil(): void
    {
        $response = $this->get(route('profil.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->admin)->followingRedirects()->get(route('profil.index'));

        $response->assertOk();
        $response->assertSee('Bambang Sudirman');
        $response->assertSee('bambang@sekolah.sch.id');
        $response->assertSee('Profil Saya');
    }

    public function test_user_can_update_profile_info(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profil.update'), [
            'name' => 'Bambang Sudirman, M.Pd.',
            'email' => 'bambang.baru@sekolah.sch.id',
        ]);

        $response->assertRedirect(route('profil.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Bambang Sudirman, M.Pd.',
            'email' => 'bambang.baru@sekolah.sch.id',
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $this->admin->id,
            'aksi' => 'user.profil_diubah',
        ]);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profil.password'), [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('profil.index'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $this->admin->fresh()->password));

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $this->admin->id,
            'aksi' => 'user.ganti_password',
        ]);
    }

    public function test_user_cannot_set_password_shorter_than_eight_characters(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profil.password'), [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password123', $this->admin->fresh()->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profil.password'), [
            'current_password' => 'salah_password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->admin->fresh()->password));
    }

    public function test_header_top_right_contains_user_profile_menu_and_profil_sekolah_for_admin(): void
    {
        // Admin sees the Filament profile menu and school profile navigation.
        $responseAdmin = $this->actingAs($this->admin)->followingRedirects()->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Profil Saya');
        $responseAdmin->assertSee('Keluar');
        $responseAdmin->assertSee('Profil & Kop Sekolah');
    }

    public function test_header_petugas_tidak_menampilkan_profil_sekolah(): void
    {
        $responsePetugas = $this->actingAs($this->petugas)->followingRedirects()->get(route('dashboard'));
        $responsePetugas->assertOk();
        $responsePetugas->assertSee('Profil Saya');
        $responsePetugas->assertDontSee('Profil & Kop Sekolah');
    }
}
