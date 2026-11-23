<?php

namespace App\Contracts;

use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\WebhookPaymentResult;
use Illuminate\Http\Request;

interface PaymentGatewayDriver
{
    /**
     * Create a checkout session/payment link for the given student payment order.
     *
     * @return array{payment_url: ?string, snap_token: ?string}
     */
    public function createCheckout(
        StudentPaymentOrder $order,
        TenantPaymentGateway $config,
        string $returnUrl,
        string $webhookUrl,
    ): array;

    /**
     * Verify incoming webhook request and normalize the payment status.
     */
    public function verifyWebhook(Request $request, TenantPaymentGateway $config): WebhookPaymentResult;
}
