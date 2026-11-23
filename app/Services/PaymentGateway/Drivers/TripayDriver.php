<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\PaymentGatewayDriver;
use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TripayDriver implements PaymentGatewayDriver
{
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array {
        $apiKey = $config->credential('api_key');
        $privateKey = $config->credential('private_key');
        $merchantCode = $config->credential('merchant_code');
        $channel = $config->credential('default_channel', 'QRIS');

        if ($apiKey === '' || $privateKey === '' || $merchantCode === '') {
            throw new RuntimeException('Kredensial Tripay (Merchant Code / API Key / Private Key) belum lengkap.');
        }

        $baseUrl = $config->is_production
            ? 'https://tripay.co.id/api'
            : 'https://tripay.co.id/api-sandbox';

        $amount = (int) $order->total_amount;
        $signature = hash_hmac('sha256', $merchantCode.$order->order_number.$amount, $privateKey);
        $siswa = $order->siswa;

        $orderItems = collect($order->items ?? [])->map(fn (array $item): array => [
            'name' => (string) ($item['label'] ?? 'Pembayaran SPP'),
            'price' => (int) ($item['nominal'] ?? 0),
            'quantity' => 1,
        ])->values()->all();

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post("{$baseUrl}/transaction/create", [
                'method' => $channel,
                'merchant_ref' => $order->order_number,
                'amount' => $amount,
                'customer_name' => $siswa?->nama ?? 'Siswa',
                'customer_email' => $order->user?->email ?? 'siswa@sekolah.sch.id',
                'customer_phone' => $siswa?->no_telp ?? '081234567890',
                'order_items' => $orderItems,
                'callback_url' => $webhookUrl,
                'return_url' => $returnUrl,
                'expired_time' => now()->addHours(24)->timestamp,
                'signature' => $signature,
            ]);

        if (! $response->successful() || ! data_get($response->json(), 'success')) {
            throw new RuntimeException('Gagal membuat transaksi Tripay: '.$response->body());
        }

        $checkoutUrl = (string) data_get($response->json(), 'data.checkout_url', '');

        if ($checkoutUrl === '') {
            throw new RuntimeException('Tripay tidak mengembalikan checkout_url.');
        }

        return [
            'payment_url' => $checkoutUrl,
            'snap_token' => null,
        ];
    }

    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult
    {
        $privateKey = $config->credential('private_key');
        $incomingSignature = (string) $request->header('X-Callback-Signature', '');
        $rawBody = $request->getContent();

        if ($privateKey === '' || $incomingSignature === '') {
            return new WebhookPaymentResult(false, false, false, '', 0, '', [], 'Missing Tripay signature');
        }

        $expectedSignature = hash_hmac('sha256', $rawBody, $privateKey);

        if (! hash_equals($expectedSignature, $incomingSignature)) {
            return new WebhookPaymentResult(false, false, false, '', 0, '', [], 'Invalid Tripay signature');
        }

        $payload = $request->json()->all();
        $merchantRef = (string) ($payload['merchant_ref'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $amount = (int) ($payload['total_amount'] ?? 0);

        return new WebhookPaymentResult(
            valid: true,
            paid: $status === 'PAID',
            failedOrExpired: in_array($status, ['EXPIRED', 'FAILED', 'REFUND'], true),
            orderNumber: $merchantRef,
            amount: $amount,
            eventId: (string) ($payload['reference'] ?? "{$merchantRef}:{$status}"),
            rawPayload: $payload,
        );
    }
}
