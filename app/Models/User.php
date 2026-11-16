<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_super_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Determine whether the user can access a Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'super-admin') {
            return $this->isSuperAdmin();
        }

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
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Determine whether the user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Tenants (schools) assigned to this user.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isSuperAdmin()) {
            return Tenant::query()->where('is_active', true)->get();
        }

        return $this->tenants()->where('is_active', true)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->tenants()
            ->whereKey($tenant)
            ->where('is_active', true)
            ->exists();
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
