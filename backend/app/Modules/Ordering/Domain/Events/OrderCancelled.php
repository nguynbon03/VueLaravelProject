<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Events;

use App\Core\SharedKernel\Domain\Events\DomainEvent;

final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public string $orderNumber,
        public string $previousStatus,
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
        return 'OrderCancelled';
    }

    public function payload(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'previous_status' => $this->previousStatus,
        ];
    }
}
