<?php

namespace App\Support;

use App\Models\Tenant;
use Filament\Facades\Filament;

class TenantContext
{
    protected static ?int $tenantId = null;

    public static function set(?Tenant $tenant): void
    {
        static::$tenantId = $tenant?->getKey();
    }

    public static function setId(?int $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    public static function clear(): void
    {
        static::$tenantId = null;
    }

    public static function id(): ?int
    {
        if (static::$tenantId !== null) {
            return static::$tenantId;
        }

        if (class_exists(Filament::class)) {
            $tenant = Filament::getTenant();

            if ($tenant !== null) {
                return (int) $tenant->getKey();
            }
        }

        return null;
    }
}
