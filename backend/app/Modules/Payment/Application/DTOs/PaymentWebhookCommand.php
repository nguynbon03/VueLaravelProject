<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTOs;

final readonly class PaymentWebhookCommand
{
    /**
     * @param  array<string, mixed>  $decodedPayload
     */
    public function __construct(
        public string $provider,
        public string $rawPayload,
        public string $signature,
        public string $eventId,
        public string $orderNumber,
        public int $amountMinor,
        public string $transactionRef,
        public array $decodedPayload,
    ) {}
}
