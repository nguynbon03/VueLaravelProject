<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Catalog\Domain\Contracts\CatalogRepositoryPort;
use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Catalog\Domain\Entities\CategoryEntity;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Entities\ProductVariantEntity;
use App\Modules\Catalog\Domain\Entities\VariantSnapshot;
use App\Modules\Catalog\Domain\ValueObjects\NewProductSpec;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentCatalogAdapter implements CatalogRepositoryPort, CatalogVariantReadPort
{
    public function listCategories(): array
    {
        $counts = DB::table('catalog_products')
            ->selectRaw('category_id, COUNT(*) as total')
            ->where('is_active', true)
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $rows = DB::table('catalog_categories')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = new CategoryEntity(
                id: (int) $row->id,
                name: (string) $row->name,
                slug: (string) $row->slug,
                description: $row->description !== null ? (string) $row->description : null,
                productsCount: isset($counts[$row->id]) ? (int) $counts[$row->id] : 0,
            );
        }

        return $categories;
    }

    public function listProducts(?string $search = null, ?string $categoryFilter = null): array
    {
        $query = DB::table('catalog_products as p')
            ->join('catalog_categories as c', 'c.id', '=', 'p.category_id')
            ->select([
                'p.*',
                'c.name as category_name',
                'c.slug as category_slug',
                'c.description as category_description',
            ])
            ->where('p.is_active', true);

        if ($categoryFilter !== null && trim($categoryFilter) !== '') {
            $cat = trim($categoryFilter);
            if (ctype_digit($cat)) {
                $query->where('p.category_id', (int) $cat);
            } else {
                $query->where('c.slug', $cat);
            }
        }

        if ($search !== null && trim($search) !== '') {
            $keyword = '%'.trim($search).'%';
            $query->where(function ($sub) use ($keyword): void {
                $sub->where('p.name', 'like', $keyword)
                    ->orWhere('p.brand', 'like', $keyword)
                    ->orWhere('p.short_description', 'like', $keyword)
                    ->orWhere('p.description', 'like', $keyword)
                    ->orWhereExists(function ($variantSub) use ($keyword): void {
                        $variantSub->select(DB::raw(1))
                            ->from('catalog_variants as cv')
                            ->whereColumn('cv.product_id', 'p.id')
                            ->where('cv.sku', 'like', $keyword);
                    });
            });
        }

        $productRows = $query->orderBy('p.id')->get();

        return $this->mapProductRows($productRows);
    }

    public function findProductBySlug(string $slug): ?ProductEntity
    {
        $rows = DB::table('catalog_products as p')
            ->join('catalog_categories as c', 'c.id', '=', 'p.category_id')
            ->select([
                'p.*',
                'c.name as category_name',
                'c.slug as category_slug',
                'c.description as category_description',
            ])
            ->where('p.slug', $slug)
            ->where('p.is_active', true)
            ->limit(1)
            ->get();

        $mapped = $this->mapProductRows($rows);

        return $mapped[0] ?? null;
    }

    public function findProductById(int $productId): ?ProductEntity
    {
        $rows = DB::table('catalog_products as p')
            ->join('catalog_categories as c', 'c.id', '=', 'p.category_id')
            ->select([
                'p.*',
                'c.name as category_name',
                'c.slug as category_slug',
                'c.description as category_description',
            ])
            ->where('p.id', $productId)
            ->limit(1)
            ->get();

        $mapped = $this->mapProductRows($rows);

        return $mapped[0] ?? null;
    }

    public function createProduct(NewProductSpec $spec): ProductEntity
    {
        $categoryExists = DB::table('catalog_categories')->where('id', $spec->categoryId)->exists();
        if (! $categoryExists) {
            throw new DomainException('Danh mục sản phẩm không tồn tại.');
        }

        $now = now();
        $baseSlug = Str::slug($spec->name);
        if ($baseSlug === '') {
            $baseSlug = 'san-pham';
        }
        $slug = $baseSlug;
        $counter = 1;
        while (DB::table('catalog_products')->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.(++$counter);
        }

        $productId = (int) DB::table('catalog_products')->insertGetId([
            'category_id' => $spec->categoryId,
            'name' => trim($spec->name),
            'slug' => $slug,
            'brand' => trim($spec->brand),
            'short_description' => trim($spec->shortDescription),
            'description' => trim($spec->description),
            'image_url' => trim($spec->imageUrl),
            'currency' => 'VND',
            'is_featured' => $spec->isFeatured,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($spec->variants as $variant) {
            DB::table('catalog_variants')->insert([
                'product_id' => $productId,
                'sku' => strtoupper(trim($variant->sku)),
                'color' => trim($variant->color),
                'size' => trim($variant->size),
                'price_minor' => $variant->price->amountMinor,
                'compare_at_minor' => $variant->compareAtPrice?->amountMinor,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $created = $this->findProductById($productId);

        if ($created === null) {
            throw new DomainException('Không thể tải lại sản phẩm vừa tạo.');
        }

        return $created;
    }

    public function getVariantsByIds(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        $rows = DB::table('catalog_variants as v')
            ->join('catalog_products as p', 'p.id', '=', 'v.product_id')
            ->select([
                'v.id as variant_id',
                'v.product_id',
                'v.sku',
                'v.color',
                'v.size',
                'v.price_minor',
                'v.compare_at_minor',
                'p.name as product_name',
                'p.slug as product_slug',
                'p.image_url',
                'p.currency',
            ])
            ->whereIn('v.id', array_values(array_unique($variantIds)))
            ->get();

        $snapshots = [];
        foreach ($rows as $row) {
            $vId = (int) $row->variant_id;
            $currency = (string) ($row->currency ?? 'VND');

            $snapshots[$vId] = new VariantSnapshot(
                variantId: $vId,
                productId: (int) $row->product_id,
                productName: (string) $row->product_name,
                productSlug: (string) $row->product_slug,
                imageUrl: (string) $row->image_url,
                sku: (string) $row->sku,
                color: (string) $row->color,
                size: (string) $row->size,
                price: new Money((int) $row->price_minor, $currency),
                compareAtPrice: $row->compare_at_minor !== null
                    ? new Money((int) $row->compare_at_minor, $currency)
                    : null,
            );
        }

        return $snapshots;
    }

    public function findProductIdBySlug(string $slug): ?int
    {
        $id = DB::table('catalog_products')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function countProducts(): int
    {
        return (int) DB::table('catalog_products')->where('is_active', true)->count();
    }

    /**
     * @param  Collection<int, object>  $productRows
     * @return array<int, ProductEntity>
     */
    private function mapProductRows(Collection $productRows): array
    {
        if ($productRows->isEmpty()) {
            return [];
        }

        $productIds = $productRows->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $variantRows = DB::table('catalog_variants')
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $entities = [];
        foreach ($productRows as $row) {
            $pId = (int) $row->id;
            $currency = (string) ($row->currency ?? 'VND');
            $variants = [];

            foreach ($variantRows->get($pId, collect()) as $vRow) {
                $variants[] = new ProductVariantEntity(
                    id: (int) $vRow->id,
                    sku: (string) $vRow->sku,
                    color: (string) $vRow->color,
                    size: (string) $vRow->size,
                    price: new Money((int) $vRow->price_minor, $currency),
                    compareAtPrice: $vRow->compare_at_minor !== null
                        ? new Money((int) $vRow->compare_at_minor, $currency)
                        : null,
                );
            }

            $entities[] = new ProductEntity(
                id: $pId,
                name: (string) $row->name,
                slug: (string) $row->slug,
                brand: (string) $row->brand,
                shortDescription: (string) $row->short_description,
                description: (string) $row->description,
                category: new CategoryEntity(
                    id: (int) $row->category_id,
                    name: (string) $row->category_name,
                    slug: (string) $row->category_slug,
                    description: $row->category_description !== null ? (string) $row->category_description : null,
                ),
                imageUrl: (string) $row->image_url,
                currency: $currency,
                isFeatured: (bool) $row->is_featured,
                createdAtIso: Carbon::parse($row->created_at)->toIso8601String(),
                variants: $variants,
            );
        }

        return $entities;
    }
}
