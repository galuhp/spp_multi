<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'message', 'type', 'target_type', 'is_active', 'starts_at', 'ends_at', 'created_by'])]
class TenantAnnouncement extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_announcement_tenant');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope announcements that are currently active and targeted to the given tenant.
     */
    public function scopeActiveForTenant(Builder $query, Tenant $tenant): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where(function (Builder $q) use ($tenant): void {
                $q->where('target_type', 'all')
                    ->orWhereHas('tenants', function (Builder $tenantQuery) use ($tenant): void {
                        $tenantQuery->whereKey($tenant->getKey());
                    });
            })
            ->latest('id');
    }
}
