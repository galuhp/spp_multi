<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sistem Pembayaran SPP');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas1',
            'password' => Hash::make('password'),
            'role' => UserRole::Petugas,
        ]);

        $response = $this->post('/login', [
            'login' => 'petugas1',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas1',
            'password' => Hash::make('password'),
            'role' => UserRole::Petugas,
        ]);

        $this->post('/login', [
            'login' => 'petugas1',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas1',
            'role' => UserRole::Petugas,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_prism_theme_and_color_scheme_controls_are_rendered(): void
    {
        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('name="color-scheme" content="light dark"', false);
        $loginResponse->assertSee('data-theme-toggle', false);
        $loginResponse->assertSee('prism-canvas', false);

        $user = User::factory()->create([
            'username' => 'admin_prism',
            'role' => UserRole::Admin,
        ]);

        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('name="color-scheme" content="light dark"', false);
        $dashboardResponse->assertSee('data-theme-toggle', false);
        $dashboardResponse->assertSee('prism-hero', false);
    }
}
