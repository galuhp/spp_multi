<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\PaymentGatewayDriver;
use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DokuDriver implements PaymentGatewayDriver
{
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array {
        $clientId = $config->credential('client_id');
        $secretKey = $config->credential('secret_key');

        if ($clientId === '' || $secretKey === '') {
            throw new RuntimeException('Kredensial DOKU (Client ID / Secret Key) belum lengkap.');
        }

        $baseUrl = $config->is_production
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';

        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $requestTarget = '/checkout/v1/payment';

        $lineItems = collect($order->items ?? [])->map(fn (array $item): array => [
            'name' => Str::limit((string) ($item['label'] ?? 'Pembayaran SPP'), 60, ''),
            'price' => (int) ($item['nominal'] ?? 0),
            'quantity' => 1,
        ])->values()->all();

        $siswa = $order->siswa;

        $payload = [
            'order' => [
                'amount' => (int) $order->total_amount,
                'invoice_number' => $order->order_number,
                'currency' => 'IDR',
                'callback_url' => $returnUrl,
                'line_items' => $lineItems,
            ],
            'payment' => [
                'payment_due_date' => 60,
            ],
            'customer' => [
                'name' => $siswa?->nama ?? 'Siswa',
                'email' => $order->user?->email ?? 'siswa@sekolah.sch.id',
            ],
        ];

        $bodyJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $digest = base64_encode(hash('sha256', $bodyJson, true));
        $signature = $this->generateSignature($clientId, $requestId, $timestamp, $requestTarget, $digest, $secretKey);

        $response = Http::withHeaders([
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $signature,
            'Content-Type' => 'application/json',
        ])
            ->timeout(20)
            ->withBody($bodyJson, 'application/json')
            ->post($baseUrl.$requestTarget);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat pembayaran DOKU: '.$response->body());
        }

        $paymentUrl = (string) data_get($response->json(), 'response.payment.url', '');

        if ($paymentUrl === '') {
            throw new RuntimeException('DOKU tidak mengembalikan URL pembayaran.');
        }

        return [
            'payment_url' => $paymentUrl,
            'snap_token' => null,
        ];
    }

    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult
    {
        $clientId = (string) $request->header('Client-Id', '');
        $requestId = (string) $request->header('Request-Id', '');
        $timestamp = (string) $request->header('Request-Timestamp', '');
        $signature = (string) $request->header('Signature', '');
        $rawBody = $request->getContent();
        $requestTarget = '/'.$request->path();

        $expectedClientId = $config->credential('client_id');
        $secretKey = $config->credential('secret_key');

        if ($expectedClientId === '' || $secretKey === '' || ! hash_equals($expectedClientId, $clientId)) {
            return new WebhookPaymentResult(false, false, false, '', 0, $requestId, [], 'Client-Id mismatch');
        }

        $digest = base64_encode(hash('sha256', $rawBody, true));
        $expectedSignature = $this->generateSignature($clientId, $requestId, $timestamp, $requestTarget, $digest, $secretKey);

        if (! hash_equals($expectedSignature, $signature)) {
            return new WebhookPaymentResult(false, false, false, '', 0, $requestId, [], 'Invalid signature');
        }

        try {
            $parsedTime = Carbon::parse($timestamp)->utc();
            if (abs(now()->utc()->diffInSeconds($parsedTime, false)) > 300) {
                return new WebhookPaymentResult(false, false, false, '', 0, $requestId, [], 'Stale timestamp');
            }
        } catch (Throwable) {
            return new WebhookPaymentResult(false, false, false, '', 0, $requestId, [], 'Invalid timestamp');
        }

        $payload = $request->json()->all();
        $orderNumber = (string) data_get($payload, 'order.invoice_number', '');
        $amount = (int) data_get($payload, 'order.amount', 0);
        $status = strtoupper((string) data_get($payload, 'transaction.status', ''));

        return new WebhookPaymentResult(
            valid: true,
            paid: $status === 'SUCCESS',
            failedOrExpired: in_array($status, ['FAILED', 'EXPIRED'], true),
            orderNumber: $orderNumber,
            amount: $amount,
            eventId: $requestId !== '' ? $requestId : "{$orderNumber}:{$status}",
            rawPayload: $payload,
        );
    }

    protected function generateSignature(
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        string $digest,
        string $secretKey,
    ): string {
        $component = "Client-Id:{$clientId}\n"
            ."Request-Id:{$requestId}\n"
            ."Request-Timestamp:{$timestamp}\n"
            ."Request-Target:{$requestTarget}\n"
            ."Digest:{$digest}";

        $hmac = hash_hmac('sha256', $component, $secretKey, true);

        return 'HMACSHA256='.base64_encode($hmac);
    }
}
