<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Actions;

use App\Modules\Ordering\Domain\Contracts\OrderingRepositoryPort;
use App\Modules\Ordering\Domain\Entities\OrderEntity;
use App\Modules\Ordering\Domain\Exceptions\UnauthorizedOrderAccessException;

final readonly class OrderQueryActions
{
    public function __construct(
        private OrderingRepositoryPort $orderingRepository,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCustomerOrders(int $userId): array
    {
        return array_map(
            static fn (OrderEntity $order): array => $order->toArray(),
            $this->orderingRepository->listByUserId($userId),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrderDetail(
        string $orderNumber,
        ?int $requesterUserId,
        ?string $requesterRole,
    ): ?array {
        $order = $this->orderingRepository->findByOrderNumber($orderNumber);

        if ($order === null) {
            return null;
        }

        if ($order->customer->userId !== null) {
            $isAdmin = $requesterRole === 'admin';
            $isOwner = $requesterUserId !== null && $requesterUserId === $order->customer->userId;

            if (! $isAdmin && ! $isOwner) {
                throw new UnauthorizedOrderAccessException();
            }
        }

        return $order->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAdminOrders(): array
    {
        return array_map(
            static fn (OrderEntity $order): array => $order->toArray(),
            $this->orderingRepository->listAllOrders(),
        );
    }
}
