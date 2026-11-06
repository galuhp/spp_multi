<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_google_login_button(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Username / NISN');
        $response->assertSee('Login with Google');
    }

    public function test_google_login_authenticates_user_with_registered_email(): void
    {
        $user = User::factory()->create([
            'email' => 'guru@sekolah.test',
        ]);

        $this->mockSocialiteUser('google-123', 'guru@sekolah.test', 'Guru Sekolah');

        $response = $this->get('/admin/oauth/callback/google');

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('socialite_users', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    public function test_google_login_rejects_email_that_is_not_registered(): void
    {
        $this->mockSocialiteUser('google-456', 'tidak-terdaftar@sekolah.test', 'Tidak Terdaftar');

        $response = $this->get('/admin/oauth/callback/google');

        $response->assertRedirect('/admin/login');
        $response->assertSessionHas(
            'filament-socialite-login-error',
            'Registration of a new user is not allowed.',
        );
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('socialite_users', 0);
    }

    private function mockSocialiteUser(string $id, string $email, string $name): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn(SocialiteUser::fake([
                'id' => $id,
                'email' => $email,
                'name' => $name,
            ]));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
