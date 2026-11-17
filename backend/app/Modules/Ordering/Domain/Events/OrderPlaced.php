<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Events;

use App\Core\SharedKernel\Domain\Events\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public string $orderNumber,
        public ?int $userId,
        public int $grandTotalMinor,
        public string $currency,
        public string $paymentMethod,
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
        return 'OrderPlaced';
    }

    public function payload(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'user_id' => $this->userId,
            'grand_total_minor' => $this->grandTotalMinor,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
        ];
    }
}
