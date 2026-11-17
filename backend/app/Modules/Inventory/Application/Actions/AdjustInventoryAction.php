<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use DomainException;

final readonly class AdjustInventoryAction
{
    public function __construct(
        private InventoryReservationPort $inventoryReservations,
        private CatalogVariantReadPort $catalogVariants,
    ) {}

    /**
     * @return array{variant_id: int, sku: string, available_stock: int}
     */
    public function execute(int $variantId, int $delta, string $reason, ?int $actorId = null): array
    {
        $variants = $this->catalogVariants->getVariantsByIds([$variantId]);

        if (! isset($variants[$variantId])) {
            throw new DomainException(sprintf('Không tìm thấy biến thể sản phẩm #%d.', $variantId));
        }

        $stockItem = $this->inventoryReservations->adjustStock(
            variantId: $variantId,
            delta: $delta,
            reason: trim($reason),
            actorId: $actorId,
        );

        return [
            'variant_id' => $stockItem->variantId,
            'sku' => $variants[$variantId]->sku,
            'available_stock' => $stockItem->availableStock,
        ];
    }
}
