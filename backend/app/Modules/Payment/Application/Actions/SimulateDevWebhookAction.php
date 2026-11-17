<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Actions;

use App\Modules\Ordering\Domain\Contracts\OrderPaymentSettlementPort;
use App\Modules\Payment\Application\DTOs\PaymentWebhookCommand;
use App\Modules\Payment\Domain\Services\HmacSignatureVerifier;
use DomainException;

final readonly class SimulateDevWebhookAction
{
    public function __construct(
        private OrderPaymentSettlementPort $orderSettlement,
        private HmacSignatureVerifier $verifier,
        private ProcessPaymentWebhookAction $processWebhookAction,
    ) {}

    /**
     * @return array{order_number: string, status: string, payment_status: string}
     */
    public function execute(string $orderNumber): array
    {
        $target = $this->orderSettlement->findSettlementTarget($orderNumber);

        if ($target === null) {
            throw new DomainException(sprintf('Không tìm thấy đơn hàng %s.', $orderNumber));
        }

        $payload = [
            'event_id' => sprintf('evt_sim_%s_%s', $orderNumber, bin2hex(random_bytes(4))),
            'order_number' => $orderNumber,
            'amount_minor' => $target->grandTotalMinor,
            'transaction_ref' => 'SIM-VIETQR-'.strtoupper(bin2hex(random_bytes(3))),
        ];

        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $secret = (string) config('services.webhooks.sepay', '');
        $signature = $this->verifier->sign($rawPayload, $secret);

        $result = $this->processWebhookAction->execute(new PaymentWebhookCommand(
            provider: 'sepay',
            rawPayload: $rawPayload,
            signature: $signature,
            eventId: $payload['event_id'],
            orderNumber: $orderNumber,
            amountMinor: $payload['amount_minor'],
            transactionRef: $payload['transaction_ref'],
            decodedPayload: $payload,
        ));

        return [
            'order_number' => $orderNumber,
            'status' => $result['order_status'],
            'payment_status' => $result['payment_status'],
        ];
    }
}
