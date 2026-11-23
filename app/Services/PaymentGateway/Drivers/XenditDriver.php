<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\PaymentGatewayDriver;
use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditDriver implements PaymentGatewayDriver
{
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array {
        $secretKey = $config->credential('secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('Kredensial Xendit (Secret API Key) belum lengkap.');
        }

        $siswa = $order->siswa;

        $payload = [
            'external_id' => $order->order_number,
            'amount' => (int) $order->total_amount,
            'currency' => 'IDR',
            'description' => 'Pembayaran SPP - '.($siswa?->nama ?? 'Siswa'),
            'success_redirect_url' => $returnUrl,
            'failure_redirect_url' => $returnUrl,
            'customer' => [
                'given_names' => $siswa?->nama ?? 'Siswa',
                'email' => $order->user?->email ?? 'siswa@sekolah.sch.id',
            ],
        ];

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post('https://api.xendit.co/v2/invoices', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat invoice Xendit: '.$response->body());
        }

        $invoiceUrl = (string) data_get($response->json(), 'invoice_url', '');

        if ($invoiceUrl === '') {
            throw new RuntimeException('Xendit tidak mengembalikan invoice_url.');
        }

        return [
            'payment_url' => $invoiceUrl,
            'snap_token' => null,
        ];
    }

    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult
    {
        $expectedToken = $config->credential('callback_token');
        $incomingToken = (string) $request->header('x-callback-token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $incomingToken)) {
            return new WebhookPaymentResult(false, false, false, '', 0, '', [], 'Invalid Xendit callback token');
        }

        $payload = $request->json()->all();
        $externalId = (string) ($payload['external_id'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $amount = (int) ($payload['paid_amount'] ?? $payload['amount'] ?? 0);

        return new WebhookPaymentResult(
            valid: true,
            paid: in_array($status, ['PAID', 'SETTLED'], true),
            failedOrExpired: in_array($status, ['EXPIRED', 'FAILED'], true),
            orderNumber: $externalId,
            amount: $amount,
            eventId: (string) ($payload['id'] ?? "{$externalId}:{$status}"),
            rawPayload: $payload,
        );
    }
}
