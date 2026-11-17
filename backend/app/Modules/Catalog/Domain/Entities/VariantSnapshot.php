<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class VariantSnapshot
{
    public function __construct(
        public int $variantId,
        public int $productId,
        public string $productName,
        public string $productSlug,
        public string $imageUrl,
        public string $sku,
        public string $color,
        public string $size,
        public Money $price,
        public ?Money $compareAtPrice = null,
    ) {}

    public function variantLabel(): string
    {
        return sprintf('%s / %s', $this->color, $this->size);
    }
}
