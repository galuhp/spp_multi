<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\PaymentGatewayDriver;
use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransDriver implements PaymentGatewayDriver
{
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array {
        $serverKey = $config->credential('server_key');

        if ($serverKey === '') {
            throw new RuntimeException('Kredensial Midtrans (Server Key) belum lengkap.');
        }

        $baseUrl = $config->is_production
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        $itemDetails = collect($order->items ?? [])->values()->map(fn (array $item, int $idx): array => [
            'id' => 'ITEM-'.($idx + 1),
            'price' => (int) ($item['nominal'] ?? 0),
            'quantity' => 1,
            'name' => Str::limit((string) ($item['label'] ?? 'Pembayaran SPP'), 50, ''),
        ])->all();

        $siswa = $order->siswa;

        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total_amount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $siswa?->nama ?? 'Siswa',
                'email' => $order->user?->email ?? 'siswa@sekolah.sch.id',
                'phone' => $siswa?->no_telp ?? '',
            ],
            'callbacks' => [
                'finish' => $returnUrl,
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post("{$baseUrl}/snap/v1/transactions", $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans: '.$response->body());
        }

        $data = $response->json();
        $redirectUrl = (string) ($data['redirect_url'] ?? '');
        $snapToken = (string) ($data['token'] ?? '');

        if ($redirectUrl === '') {
            throw new RuntimeException('Midtrans tidak mengembalikan redirect_url.');
        }

        return [
            'payment_url' => $redirectUrl,
            'snap_token' => $snapToken !== '' ? $snapToken : null,
        ];
    }

    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult
    {
        $serverKey = $config->credential('server_key');
        $payload = $request->json()->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($serverKey === '' || $orderId === '' || $signatureKey === '') {
            return new WebhookPaymentResult(false, false, false, '', 0, '', $payload, 'Missing Midtrans signature/fields');
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expected, $signatureKey)) {
            return new WebhookPaymentResult(false, false, false, '', 0, '', $payload, 'Invalid Midtrans signature');
        }

        $txStatus = strtolower((string) ($payload['transaction_status'] ?? ''));
        $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? 'accept'));

        $paid = ($txStatus === 'capture' && $fraudStatus === 'accept') || $txStatus === 'settlement';
        $failed = in_array($txStatus, ['deny', 'cancel', 'expire', 'failure'], true);

        return new WebhookPaymentResult(
            valid: true,
            paid: $paid,
            failedOrExpired: $failed,
            orderNumber: $orderId,
            amount: (int) round((float) $grossAmount),
            eventId: (string) ($payload['transaction_id'] ?? "{$orderId}:{$txStatus}"),
            rawPayload: $payload,
        );
    }
}
