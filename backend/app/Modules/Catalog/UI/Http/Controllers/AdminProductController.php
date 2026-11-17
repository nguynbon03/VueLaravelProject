<?php

declare(strict_types=1);

namespace App\Modules\Catalog\UI\Http\Controllers;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Actions\CreateAdminProductAction;
use App\Modules\Catalog\Domain\ValueObjects\NewProductSpec;
use App\Modules\Catalog\Domain\ValueObjects\NewVariantSpec;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminProductController extends Controller
{
    public function __construct(
        private readonly CreateAdminProductAction $createProductAction,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:catalog_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:150'],
            'short_description' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string'],
            'image_url' => ['required', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct', 'unique:catalog_variants,sku'],
            'variants.*.color' => ['required', 'string', 'max:100'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.price_minor' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_minor' => ['nullable', 'integer', 'min:0'],
            'variants.*.initial_stock' => ['required', 'integer', 'min:0'],
        ]);

        $variantSpecs = [];
        foreach ($validated['variants'] as $v) {
            $variantSpecs[] = new NewVariantSpec(
                sku: (string) $v['sku'],
                color: (string) $v['color'],
                size: (string) $v['size'],
                price: Money::vnd((int) $v['price_minor']),
                initialStock: (int) $v['initial_stock'],
                compareAtPrice: isset($v['compare_at_minor']) ? Money::vnd((int) $v['compare_at_minor']) : null,
            );
        }

        try {
            $created = $this->createProductAction->execute(new NewProductSpec(
                categoryId: (int) $validated['category_id'],
                name: (string) $validated['name'],
                brand: (string) $validated['brand'],
                shortDescription: (string) $validated['short_description'],
                description: (string) $validated['description'],
                imageUrl: (string) $validated['image_url'],
                variants: $variantSpecs,
                isFeatured: (bool) ($validated['is_featured'] ?? false),
            ));
        } catch (DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }

        return new JsonResponse(['data' => $created], 201);
    }
}
