<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Actions;

use App\Modules\Ordering\Domain\Contracts\OrderPaymentSettlementPort;
use App\Modules\Payment\Application\DTOs\PaymentWebhookCommand;
use App\Modules\Payment\Domain\Contracts\PaymentInboxPort;
use App\Modules\Payment\Domain\Services\HmacSignatureVerifier;
use App\Modules\Payment\Domain\ValueObjects\PaymentTransactionRecord;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class ProcessPaymentWebhookAction
{
    public function __construct(
        private HmacSignatureVerifier $verifier,
        private PaymentInboxPort $inbox,
        private OrderPaymentSettlementPort $orderSettlement,
    ) {}

    /**
     * @return array{acknowledged: bool, duplicate: bool, order_status: string, payment_status: string}
     */
    public function execute(PaymentWebhookCommand $command): array
    {
        $secret = (string) config('services.webhooks.'.$command->provider, '');
        $this->verifier->verify($command->rawPayload, $command->signature, $secret);

        return DB::transaction(function () use ($command): array {
            $target = $this->orderSettlement->findSettlementTarget($command->orderNumber);

            if ($target === null) {
                throw new DomainException(sprintf('Không tìm thấy đơn hàng %s.', $command->orderNumber));
            }

            $firstSeen = $this->inbox->recordIfFirstSeen(
                provider: $command->provider,
                externalEventId: $command->eventId,
                payload: $command->decodedPayload,
            );

            if (! $firstSeen) {
                return [
                    'acknowledged' => true,
                    'duplicate' => true,
                    'order_status' => $target->status,
                    'payment_status' => $target->paymentStatus,
                ];
            }

            $settled = $this->orderSettlement->settlePaidOrder(
                orderNumber: $command->orderNumber,
                paidAmountMinor: $command->amountMinor,
                provider: $command->provider,
                transactionRef: $command->transactionRef,
            );

            $this->inbox->recordTransaction(new PaymentTransactionRecord(
                orderNumber: $command->orderNumber,
                provider: $command->provider,
                externalEventId: $command->eventId,
                transactionRef: $command->transactionRef,
                amountMinor: $command->amountMinor,
                rawPayload: $command->decodedPayload,
            ));

            return [
                'acknowledged' => true,
                'duplicate' => false,
                'order_status' => $settled->status,
                'payment_status' => $settled->paymentStatus,
            ];
        });
    }
}
