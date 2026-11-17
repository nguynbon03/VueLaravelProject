<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\Entities\VariantSnapshot;

interface CatalogVariantReadPort
{
    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, VariantSnapshot>
     */
    public function getVariantsByIds(array $variantIds): array;

    public function findProductIdBySlug(string $slug): ?int;

    public function countProducts(): int;
}
