<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'domain', 'is_active', 'fee_per_transaction', 'settings'])]
class Tenant extends Model implements HasName
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'fee_per_transaction' => 'integer',
            'settings' => 'array',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    public function hasUnpaidInvoices(): bool
    {
        return $this->invoices()
            ->where('status', '!=', 'paid')
            ->where('total_amount', '>', 0)
            ->exists();
    }

    public function hasOverdueInvoices(): bool
    {
        return $this->invoices()
            ->where('status', '!=', 'paid')
            ->where('total_amount', '>', 0)
            ->where(function ($query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function ($dateQuery): void {
                        $dateQuery->whereNotNull('due_date')
                            ->whereDate('due_date', '<', today());
                    });
            })
            ->exists();
    }

    public function nextDueUnpaidInvoice(): ?TenantInvoice
    {
        return $this->invoices()
            ->where('status', '!=', 'paid')
            ->where('total_amount', '>', 0)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->first();
    }

    public function isUserCreationDisabled(): bool
    {
        return (bool) data_get($this->settings, 'disable_user_creation', false);
    }

    public function isEditProfilSekolahDisabled(): bool
    {
        return (bool) data_get($this->settings, 'disable_edit_profil_sekolah', false);
    }

    public function paymentGateways(): HasMany
    {
        return $this->hasMany(TenantPaymentGateway::class);
    }

    public function activePaymentGateway(): ?TenantPaymentGateway
    {
        return $this->paymentGateways()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(TenantAnnouncement::class, 'tenant_announcement_tenant');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function configuredTier(): ?string
    {
        $tier = data_get($this->settings, 'tier');

        return in_array($tier, ['basic', 'pro'], true) ? $tier : null;
    }

    public function maxSiswa(): ?int
    {
        $max = (int) data_get($this->settings, 'max_siswa', 0);

        return $max > 0 ? $max : null;
    }

    public function canAddSiswa(): bool
    {
        $max = $this->maxSiswa();

        if ($max === null) {
            return true;
        }

        return $this->siswas()->withoutGlobalScopes()->where('tenant_id', $this->id)->count() < $max;
    }
}
