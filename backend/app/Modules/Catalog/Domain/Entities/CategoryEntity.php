<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

final readonly class CategoryEntity
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $productsCount = 0,
    ) {}

    /**
     * @return array{id: int, name: string, slug: string, description: ?string, products_count: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'products_count' => $this->productsCount,
        ];
    }
}
