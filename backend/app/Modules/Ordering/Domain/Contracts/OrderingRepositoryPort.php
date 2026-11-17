<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Contracts;

use App\Modules\Ordering\Domain\Entities\OrderEntity;
use App\Modules\Ordering\Domain\ValueObjects\OrderDraft;

interface OrderingRepositoryPort
{
    public function generateUniqueOrderNumber(): string;

    public function createOrder(OrderDraft $draft): OrderEntity;

    public function findByOrderNumber(string $orderNumber): ?OrderEntity;

    /**
     * @return array<int, OrderEntity>
     */
    public function listByUserId(int $userId): array;

    /**
     * @return array<int, OrderEntity>
     */
    public function listAllOrders(): array;

    public function updateOrderStatus(string $orderNumber, string $newStatus): OrderEntity;
}
