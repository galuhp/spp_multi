<?php

namespace App\Services\PaymentGateway;

readonly class WebhookPaymentResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public bool $valid,
        public bool $paid,
        public bool $failedOrExpired,
        public string $orderNumber,
        public int $amount,
        public string $eventId,
        public array $rawPayload = [],
        public ?string $errorMessage = null,
    ) {}
}
