<?php

namespace App\Services;

use App\Models\TenantInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DokuService
{
    public function baseUrl(): string
    {
        return config('services.doku.is_production')
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    /**
     * Create a DOKU Checkout payment link for the given tenant invoice.
     */
    public function createCheckoutPayment(TenantInvoice $invoice, string $returnUrl): string
    {
        $clientId = (string) config('services.doku.client_id');
        $secretKey = (string) config('services.doku.secret_key');

        if ($clientId === '' || $secretKey === '') {
            throw new RuntimeException('Konfigurasi DOKU (DOKU_CLIENT_ID / DOKU_SECRET_KEY) belum diisi.');
        }

        $invoiceNumber = $invoice->doku_invoice_number ?: sprintf('INV-%d-%s-%s', $invoice->tenant_id, $invoice->period, Str::upper(Str::random(4)));
        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $requestTarget = '/checkout/v1/payment';

        $payload = [
            'order' => [
                'amount' => (int) $invoice->total_amount,
                'invoice_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => $returnUrl,
                'line_items' => [
                    [
                        'name' => "Tagihan Platform SPP Periode {$invoice->period} ({$invoice->transaction_count} transaksi)",
                        'price' => (int) $invoice->total_amount,
                        'quantity' => 1,
                    ],
                ],
            ],
            'payment' => [
                'payment_due_date' => 60,
            ],
            'customer' => [
                'name' => $invoice->tenant->name,
                'email' => auth()->user()?->email ?? 'admin@sekolah.sch.id',
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
        ])->withBody($bodyJson, 'application/json')
            ->post($this->baseUrl().$requestTarget);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat pembayaran DOKU: '.$response->body());
        }

        $paymentUrl = (string) data_get($response->json(), 'response.payment.url', '');

        if ($paymentUrl === '') {
            throw new RuntimeException('DOKU tidak mengembalikan URL pembayaran.');
        }

        $invoice->update([
            'doku_invoice_number' => $invoiceNumber,
            'payment_url' => $paymentUrl,
        ]);

        return $paymentUrl;
    }

    public function generateSignature(
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

        $hmac = base64_encode(hash_hmac('sha256', $component, $secretKey, true));

        return "HMACSHA256={$hmac}";
    }

    public function verifyWebhookSignature(
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        string $rawBody,
        string $receivedSignature,
    ): bool {
        $configuredClientId = (string) config('services.doku.client_id');
        $secretKey = (string) config('services.doku.secret_key');

        if ($configuredClientId === '' || $secretKey === '' || $receivedSignature === '') {
            return false;
        }

        if (! hash_equals($configuredClientId, $clientId)) {
            return false;
        }

        $digest = base64_encode(hash('sha256', $rawBody, true));
        $expected = $this->generateSignature($clientId, $requestId, $timestamp, $requestTarget, $digest, $secretKey);

        return hash_equals($expected, $receivedSignature);
    }
}
