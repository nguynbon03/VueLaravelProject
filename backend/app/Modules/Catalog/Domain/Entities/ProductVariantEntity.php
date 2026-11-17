<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class ProductVariantEntity
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $color,
        public string $size,
        public Money $price,
        public ?Money $compareAtPrice = null,
    ) {}
}
