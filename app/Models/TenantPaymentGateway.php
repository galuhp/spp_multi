<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tenant_id',
    'provider',
    'is_active',
    'is_production',
    'credentials',
    'webhook_token',
])]
class TenantPaymentGateway extends Model
{
    use BelongsToTenant;

    public const PROVIDER_DOKU = 'doku';

    public const PROVIDER_MIDTRANS = 'midtrans';

    public const PROVIDER_XENDIT = 'xendit';

    public const PROVIDER_TRIPAY = 'tripay';

    public const PROVIDER_DUITKU = 'duitku';

    /**
     * @return array<string, string>
     */
    public static function providerOptions(): array
    {
        return [
            self::PROVIDER_DOKU => 'DOKU Checkout',
            self::PROVIDER_MIDTRANS => 'Midtrans (Snap)',
            self::PROVIDER_XENDIT => 'Xendit (Invoice)',
            self::PROVIDER_TRIPAY => 'Tripay',
            self::PROVIDER_DUITKU => 'Duitku',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_production' => 'boolean',
            'credentials' => 'encrypted:array',
        ];
    }

    public function credential(string $key, string $default = ''): string
    {
        return (string) data_get($this->credentials ?? [], $key, $default);
    }

    public function providerLabel(): string
    {
        return self::providerOptions()[$this->provider] ?? strtoupper($this->provider);
    }
}
