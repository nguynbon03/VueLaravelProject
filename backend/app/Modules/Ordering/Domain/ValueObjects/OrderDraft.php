<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Ordering\Domain\Entities\OrderLineEntity;

final readonly class OrderDraft
{
    /**
     * @param  array<int, OrderLineEntity>  $lines
     * @param  array<string, mixed>  $paymentInstruction
     */
    public function __construct(
        public string $orderNumber,
        public string $status,
        public string $paymentStatus,
        public string $paymentMethod,
        public OrderPricingSnapshot $pricing,
        public CustomerSnapshot $customer,
        public array $lines,
        public array $paymentInstruction,
    ) {}
}
