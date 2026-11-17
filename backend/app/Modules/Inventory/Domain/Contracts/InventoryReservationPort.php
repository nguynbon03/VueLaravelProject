<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Contracts;

use App\Modules\Inventory\Domain\Entities\StockItem;

interface InventoryReservationPort
{
    public function initializeStock(int $variantId, int $initialStock, string $reason = 'initial_stock'): void;

    /**
     * @param  array<int, int>  $variantQuantities  Map of variant_id => quantity
     */
    public function reserveForOrder(string $orderNumber, array $variantQuantities, int $ttlMinutes = 30): void;

    /**
     * Confirm stock allocation for a paid order.
     * Returns true when stock is confirmed (active hold or safe late re-allocation),
     * or false if the hold expired/released and current stock is insufficient (preventing phantom oversell).
     */
    public function confirmForOrder(string $orderNumber): bool;

    public function releaseForOrder(string $orderNumber): void;

    public function releaseExpiredReservations(): int;

    public function adjustStock(int $variantId, int $delta, string $reason, ?int $actorId = null): StockItem;
}
