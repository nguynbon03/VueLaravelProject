<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Domain\Contracts\CatalogRepositoryPort;
use App\Modules\Catalog\Domain\ValueObjects\NewProductSpec;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use Illuminate\Support\Facades\DB;

final readonly class CreateAdminProductAction
{
    public function __construct(
        private CatalogRepositoryPort $catalogRepository,
        private InventoryReservationPort $inventoryReservations,
        private CatalogQueryActions $catalogQueries,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(NewProductSpec $spec): array
    {
        $product = DB::transaction(function () use ($spec) {
            $createdProduct = $this->catalogRepository->createProduct($spec);

            foreach ($createdProduct->variants as $index => $variant) {
                $initialStock = $spec->variants[$index]->initialStock ?? 0;
                $this->inventoryReservations->initializeStock(
                    variantId: $variant->id,
                    initialStock: $initialStock,
                    reason: sprintf('Khởi tạo sản phẩm mới (%s)', $variant->sku),
                );
            }

            return $createdProduct;
        });

        return $this->catalogQueries->formatSingleProduct($product);
    }
}
