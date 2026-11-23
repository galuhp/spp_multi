<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\PaymentGatewayDriver;
use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DuitkuDriver implements PaymentGatewayDriver
{
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array {
        $merchantCode = $config->credential('merchant_code');
        $apiKey = $config->credential('api_key');
        $paymentMethod = $config->credential('default_channel', 'SP');

        if ($merchantCode === '' || $apiKey === '') {
            throw new RuntimeException('Kredensial Duitku (Merchant Code / API Key) belum lengkap.');
        }

        $baseUrl = $config->is_production
            ? 'https://passport.duitku.com'
            : 'https://sandbox.duitku.com';

        $amount = (int) $order->total_amount;
        $signature = md5($merchantCode.$order->order_number.$amount.$apiKey);
        $siswa = $order->siswa;

        $response = Http::acceptJson()
            ->timeout(20)
            ->post("{$baseUrl}/webapi/api/merchant/v2/inquiry", [
                'merchantCode' => $merchantCode,
                'paymentAmount' => $amount,
                'paymentMethod' => $paymentMethod,
                'merchantOrderId' => $order->order_number,
                'productDetails' => 'Pembayaran SPP - '.($siswa?->nama ?? 'Siswa'),
                'email' => $order->user?->email ?? 'siswa@sekolah.sch.id',
                'phoneNumber' => $siswa?->no_telp ?? '',
                'customerVaName' => $siswa?->nama ?? 'Siswa',
                'callbackUrl' => $webhookUrl,
                'returnUrl' => $returnUrl,
                'expiryPeriod' => 60,
                'signature' => $signature,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat transaksi Duitku: '.$response->body());
        }

        $paymentUrl = (string) data_get($response->json(), 'paymentUrl', '');

        if ($paymentUrl === '') {
            throw new RuntimeException('Duitku tidak mengembalikan paymentUrl.');
        }

        return [
            'payment_url' => $paymentUrl,
            'snap_token' => null,
        ];
    }

    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult
    {
        $merchantCode = $config->credential('merchant_code');
        $apiKey = $config->credential('api_key');

        $payload = $request->all();
        $incomingMerchant = (string) ($payload['merchantCode'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');
        $resultCode = (string) ($payload['resultCode'] ?? '');

        if ($merchantCode === '' || $apiKey === '' || $incomingMerchant !== $merchantCode) {
            return new WebhookPaymentResult(false, false, false, '', 0, '', $payload, 'Duitku merchant mismatch');
        }

        $expected = md5($merchantCode.$amount.$merchantOrderId.$apiKey);

        if (! hash_equals($expected, $signature)) {
            return new WebhookPaymentResult(false, false, false, '', 0, '', $payload, 'Invalid Duitku signature');
        }

        return new WebhookPaymentResult(
            valid: true,
            paid: $resultCode === '00',
            failedOrExpired: $resultCode !== '' && $resultCode !== '00',
            orderNumber: $merchantOrderId,
            amount: (int) $amount,
            eventId: (string) ($payload['reference'] ?? "{$merchantOrderId}:{$resultCode}"),
            rawPayload: $payload,
        );
    }
}
