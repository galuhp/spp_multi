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
}
