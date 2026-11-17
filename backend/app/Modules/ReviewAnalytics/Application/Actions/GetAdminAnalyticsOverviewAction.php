<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Application\Actions;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Inventory\Domain\Contracts\InventoryStockReadPort;
use App\Modules\Ordering\Domain\Contracts\OrderAnalyticsReadPort;

final readonly class GetAdminAnalyticsOverviewAction
{
    public function __construct(
        private OrderAnalyticsReadPort $orderAnalytics,
        private CatalogVariantReadPort $catalogVariants,
        private InventoryStockReadPort $inventoryStocks,
        private OutboxPort $outbox,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $revenue = $this->orderAnalytics->getRevenueSummary();
        $productsCount = $this->catalogVariants->countProducts();
        $lowStockItems = $this->inventoryStocks->getLowStockItems(10);

        $variantIds = array_map(
            static fn ($item): int => $item->variantId,
            $lowStockItems,
        );
        $variantMap = $this->catalogVariants->getVariantsByIds($variantIds);

        $lowStockVariants = [];
        foreach ($lowStockItems as $stockItem) {
            $variant = $variantMap[$stockItem->variantId] ?? null;
            if ($variant === null) {
                continue;
            }

            $lowStockVariants[] = [
                'variant_id' => $stockItem->variantId,
                'sku' => $variant->sku,
                'product_name' => $variant->productName,
                'color' => $variant->color,
                'size' => $variant->size,
                'available_stock' => $stockItem->availableStock,
            ];
        }

        return [
            'gmv_minor' => $revenue['gmv_minor'],
            'paid_orders_count' => $revenue['paid_orders_count'],
            'total_orders_count' => $revenue['total_orders_count'],
            'avg_order_value_minor' => $revenue['avg_order_value_minor'],
            'products_count' => $productsCount,
            'low_stock_variants' => $lowStockVariants,
            'outbox_stats' => $this->outbox->stats(),
        ];
    }
}
