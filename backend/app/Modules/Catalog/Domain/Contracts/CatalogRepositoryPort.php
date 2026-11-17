<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\Entities\CategoryEntity;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\ValueObjects\NewProductSpec;

interface CatalogRepositoryPort
{
    /**
     * @return array<int, CategoryEntity>
     */
    public function listCategories(): array;

    /**
     * @return array<int, ProductEntity>
     */
    public function listProducts(?string $search = null, ?string $categoryFilter = null): array;

    public function findProductBySlug(string $slug): ?ProductEntity;

    public function findProductById(int $productId): ?ProductEntity;

    public function createProduct(NewProductSpec $spec): ProductEntity;
}
