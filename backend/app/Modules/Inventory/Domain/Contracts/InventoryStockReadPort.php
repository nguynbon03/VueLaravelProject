<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Contracts;

use App\Modules\Inventory\Domain\Entities\StockItem;

interface InventoryStockReadPort
{
    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, int>
     */
    public function getAvailableStockMap(array $variantIds): array;

    /**
     * @return array<int, StockItem>
     */
    public function getLowStockItems(int $limit = 10): array;
}
