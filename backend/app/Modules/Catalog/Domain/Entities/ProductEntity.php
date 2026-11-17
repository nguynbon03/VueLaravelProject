<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

final readonly class ProductEntity
{
    /**
     * @param  array<int, ProductVariantEntity>  $variants
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $brand,
        public string $shortDescription,
        public string $description,
        public CategoryEntity $category,
        public string $imageUrl,
        public string $currency,
        public bool $isFeatured,
        public string $createdAtIso,
        public array $variants,
    ) {}

    public function minPriceMinor(): int
    {
        if ($this->variants === []) {
            return 0;
        }

        $prices = array_map(
            static fn (ProductVariantEntity $v): int => $v->price->amountMinor,
            $this->variants,
        );

        return min($prices);
    }
}
