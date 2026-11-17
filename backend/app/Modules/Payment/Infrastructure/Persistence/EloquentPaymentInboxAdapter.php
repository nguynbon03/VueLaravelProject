<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\PaymentInboxPort;
use App\Modules\Payment\Domain\ValueObjects\PaymentTransactionRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class EloquentPaymentInboxAdapter implements PaymentInboxPort
{
    public function recordIfFirstSeen(string $provider, string $externalEventId, array $payload): bool
    {
        $exists = DB::table('inbox_messages')
            ->where('provider', $provider)
            ->where('external_event_id', $externalEventId)
            ->exists();

        if ($exists) {
            return false;
        }

        $now = now();

        try {
            DB::table('inbox_messages')->insert([
                'provider' => $provider,
                'external_event_id' => $externalEventId,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    public function recordTransaction(PaymentTransactionRecord $record): void
    {
        $now = now();

        DB::table('payment_transactions')->insert([
            'order_number' => $record->orderNumber,
            'provider' => $record->provider,
            'external_event_id' => $record->externalEventId,
            'transaction_ref' => $record->transactionRef,
            'amount_minor' => $record->amountMinor,
            'currency' => 'VND',
            'status' => 'succeeded',
            'raw_payload' => json_encode($record->rawPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
