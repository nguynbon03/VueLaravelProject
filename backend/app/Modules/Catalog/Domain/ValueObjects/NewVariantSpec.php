<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObjects;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class NewVariantSpec
{
    public function __construct(
        public string $sku,
        public string $color,
        public string $size,
        public Money $price,
        public int $initialStock,
        public ?Money $compareAtPrice = null,
    ) {}
}
