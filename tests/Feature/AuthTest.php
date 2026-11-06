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
        $this->get('/login')->assertRedirect('/admin/login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Username / NISN');
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

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'login' => 'tidak-ada',
                'password' => 'password-salah',
            ]);
        }

        $this->post('/login', [
            'login' => 'tidak-ada',
            'password' => 'password-salah',
        ])->assertStatus(429);
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
}
