<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Contracts;

interface OrderAnalyticsReadPort
{
    public function hasUserPurchasedProduct(int $userId, int $productId): bool;

    /**
     * @return array{gmv_minor: int, paid_orders_count: int, total_orders_count: int, avg_order_value_minor: int}
     */
    public function getRevenueSummary(): array;

    /**
     * @return array<int, int> Map of user_id => orders_count
     */
    public function getOrderCountsByUserId(): array;
}
