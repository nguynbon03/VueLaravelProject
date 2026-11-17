<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Events;

use App\Core\SharedKernel\Domain\Events\DomainEvent;

final readonly class OrderPaid implements DomainEvent
{
    public function __construct(
        public string $orderNumber,
        public int $paidAmountMinor,
        public string $provider,
        public string $transactionRef,
    ) {}

    public function aggregateType(): string
    {
        return 'Order';
    }

    public function aggregateId(): string
    {
        return $this->orderNumber;
    }

    public function eventType(): string
    {
        return 'OrderPaid';
    }

    public function payload(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'paid_amount_minor' => $this->paidAmountMinor,
            'provider' => $this->provider,
            'transaction_ref' => $this->transactionRef,
        ];
    }
}
