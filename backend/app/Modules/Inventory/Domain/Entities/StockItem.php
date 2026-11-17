<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Entities;

final readonly class StockItem
{
    public function __construct(
        public int $variantId,
        public int $availableStock,
        public int $reservedStock,
        public int $soldStock,
        public int $lowStockThreshold = 5,
    ) {}
}
