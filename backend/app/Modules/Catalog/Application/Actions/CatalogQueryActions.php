<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Domain\Contracts\CatalogRepositoryPort;
use App\Modules\Catalog\Domain\Entities\CategoryEntity;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Inventory\Domain\Contracts\InventoryStockReadPort;
use App\Modules\ReviewAnalytics\Domain\Contracts\ProductReviewReadPort;
use App\Modules\ReviewAnalytics\Domain\Entities\ReviewEntity;

final readonly class CatalogQueryActions
{
    public function __construct(
        private CatalogRepositoryPort $catalogRepository,
        private InventoryStockReadPort $inventoryStocks,
        private ProductReviewReadPort $productReviews,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCategories(): array
    {
        return array_map(
            static fn (CategoryEntity $category): array => $category->toArray(),
            $this->catalogRepository->listCategories(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProducts(?string $search = null, ?string $category = null, string $sort = 'featured'): array
    {
        $products = $this->catalogRepository->listProducts($search, $category);

        if ($products === []) {
            return [];
        }

        $hydrated = $this->hydrateProducts($products);

        usort($hydrated, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'price_asc' => $a['min_price_minor'] <=> $b['min_price_minor'],
                'price_desc' => $b['min_price_minor'] <=> $a['min_price_minor'],
                'newest' => strcmp($b['_created_at'], $a['_created_at']) ?: ($b['id'] <=> $a['id']),
                default => ($b['_is_featured'] <=> $a['_is_featured']) ?: ($a['id'] <=> $b['id']),
            };
        });

        return array_map(static function (array $item): array {
            unset($item['_is_featured'], $item['_created_at']);

            return $item;
        }, $hydrated);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProductBySlug(string $slug): ?array
    {
        $product = $this->catalogRepository->findProductBySlug($slug);

        if ($product === null) {
            return null;
        }

        $hydrated = $this->hydrateProducts([$product])[0];
        unset($hydrated['_is_featured'], $hydrated['_created_at']);

        $reviews = $this->productReviews->getReviewsForProduct($product->id);
        $hydrated['reviews'] = array_map(
            static fn (ReviewEntity $review): array => $review->toArray(),
            $reviews,
        );

        return $hydrated;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSingleProduct(ProductEntity $product): array
    {
        $hydrated = $this->hydrateProducts([$product])[0];
        unset($hydrated['_is_featured'], $hydrated['_created_at']);

        return $hydrated;
    }

    /**
     * @param  array<int, ProductEntity>  $products
     * @return array<int, array<string, mixed>>
     */
    private function hydrateProducts(array $products): array
    {
        $productIds = [];
        $variantIds = [];

        foreach ($products as $product) {
            $productIds[] = $product->id;
            foreach ($product->variants as $variant) {
                $variantIds[] = $variant->id;
            }
        }

        $stockMap = $this->inventoryStocks->getAvailableStockMap($variantIds);
        $ratingMap = $this->productReviews->getRatingStatsForProducts($productIds);

        $result = [];

        foreach ($products as $product) {
            $totalStock = 0;
            $variantsPayload = [];

            foreach ($product->variants as $variant) {
                $available = $stockMap[$variant->id] ?? 0;
                $totalStock += $available;

                $variantsPayload[] = [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'color' => $variant->color,
                    'size' => $variant->size,
                    'price_minor' => $variant->price->amountMinor,
                    'compare_at_minor' => $variant->compareAtPrice?->amountMinor,
                    'available_stock' => $available,
                ];
            }

            $rating = $ratingMap[$product->id] ?? null;

            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand,
                'short_description' => $product->shortDescription,
                'description' => $product->description,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
                'image_url' => $product->imageUrl,
                'min_price_minor' => $product->minPriceMinor(),
                'currency' => $product->currency,
                'rating_avg' => $rating !== null ? $rating->ratingAvg : 0.0,
                'reviews_count' => $rating !== null ? $rating->reviewsCount : 0,
                'total_available_stock' => $totalStock,
                'variants' => $variantsPayload,
                '_is_featured' => $product->isFeatured ? 1 : 0,
                '_created_at' => $product->createdAtIso,
            ];
        }

        return $result;
    }
}
