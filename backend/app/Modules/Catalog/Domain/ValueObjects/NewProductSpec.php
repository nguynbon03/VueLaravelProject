<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class NewProductSpec
{
    /**
     * @param  array<int, NewVariantSpec>  $variants
     */
    public function __construct(
        public int $categoryId,
        public string $name,
        public string $brand,
        public string $shortDescription,
        public string $description,
        public string $imageUrl,
        public array $variants,
        public bool $isFeatured = false,
    ) {}
}
