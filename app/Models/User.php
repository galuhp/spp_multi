<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Determine whether the user can access a Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Determine whether the user has administrator role.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine whether the user has petugas role.
     */
    public function isPetugas(): bool
    {
        return $this->role === UserRole::Petugas;
    }

    /**
     * Determine whether the user has siswa role.
     */
    public function isSiswa(): bool
    {
        return $this->role === UserRole::Siswa;
    }

    /**
     * Determine whether the user has wali kelas role.
     */
    public function isWaliKelas(): bool
    {
        return $this->role === UserRole::WaliKelas;
    }

    /**
     * Check if user is staff (admin, petugas, or wali kelas).
     */
    public function isStaff(): bool
    {
        return $this->role === UserRole::Admin
            || $this->role === UserRole::Petugas
            || $this->role === UserRole::WaliKelas;
    }

    /**
     * Classes assigned to this wali kelas.
     */
    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    /**
     * Class IDs managed by this wali kelas.
     *
     * @return array<int>
     */
    public function kelasWaliIds(): array
    {
        return $this->kelasWali()->pluck('id')->all();
    }

    /**
     * Siswa profile linked to this user account (if role is siswa).
     */
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    /**
     * Socialite accounts linked to this user.
     */
    public function socialiteUsers(): HasMany
    {
        return $this->hasMany(SocialiteUser::class);
    }

    /**
     * Pembayaran records processed by this staff user.
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }
}
