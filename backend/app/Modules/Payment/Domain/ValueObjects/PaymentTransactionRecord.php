<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObjects;

final readonly class PaymentTransactionRecord
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $orderNumber,
        public string $provider,
        public string $externalEventId,
        public string $transactionRef,
        public int $amountMinor,
        public array $rawPayload,
    ) {}
}
