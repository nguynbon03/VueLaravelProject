<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Contracts;

use App\Modules\Payment\Domain\ValueObjects\PaymentTransactionRecord;

interface PaymentInboxPort
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordIfFirstSeen(string $provider, string $externalEventId, array $payload): bool;

    public function recordTransaction(PaymentTransactionRecord $record): void;
}
