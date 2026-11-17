<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Entities;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class OrderLineEntity
{
    public function __construct(
        public int $productId,
        public int $variantId,
        public string $sku,
        public string $productName,
        public string $variantLabel,
        public int $quantity,
        public Money $unitPrice,
        public Money $lineTotal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'product_name' => $this->productName,
            'variant_label' => $this->variantLabel,
            'quantity' => $this->quantity,
            'unit_price_minor' => $this->unitPrice->amountMinor,
            'line_total_minor' => $this->lineTotal->amountMinor,
        ];
    }
}
