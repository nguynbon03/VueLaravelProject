<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Entities;

final readonly class OrderSettlementTarget
{
    public function __construct(
        public string $orderNumber,
        public string $status,
        public string $paymentStatus,
        public int $grandTotalMinor,
    ) {}
}
