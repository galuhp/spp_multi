<?php

namespace App\Services\PaymentGateway;

use App\Contracts\PaymentGatewayDriver;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\Drivers\DokuDriver;
use App\Services\PaymentGateway\Drivers\DuitkuDriver;
use App\Services\PaymentGateway\Drivers\MidtransDriver;
use App\Services\PaymentGateway\Drivers\TripayDriver;
use App\Services\PaymentGateway\Drivers\XenditDriver;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(string $provider): PaymentGatewayDriver
    {
        return match ($provider) {
            TenantPaymentGateway::PROVIDER_DOKU => app(DokuDriver::class),
            TenantPaymentGateway::PROVIDER_MIDTRANS => app(MidtransDriver::class),
            TenantPaymentGateway::PROVIDER_XENDIT => app(XenditDriver::class),
            TenantPaymentGateway::PROVIDER_TRIPAY => app(TripayDriver::class),
            TenantPaymentGateway::PROVIDER_DUITKU => app(DuitkuDriver::class),
            default => throw new InvalidArgumentException("Provider payment gateway tidak dikenal: {$provider}"),
        };
    }
}
