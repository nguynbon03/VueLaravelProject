<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Actions;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Ordering\Domain\Contracts\OrderingRepositoryPort;
use App\Modules\Ordering\Domain\Events\OrderCancelled;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAdminOrderStatusAction
{
    public function __construct(
        private OrderingRepositoryPort $orderingRepository,
        private InventoryReservationPort $inventoryReservations,
        private OutboxPort $outbox,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $orderNumber, string $newStatus): array
    {
        $updated = DB::transaction(function () use ($orderNumber, $newStatus) {
            $existing = $this->orderingRepository->findByOrderNumber($orderNumber);

            if ($existing === null) {
                throw new DomainException(sprintf('Không tìm thấy đơn hàng %s.', $orderNumber));
            }

            if ($newStatus === 'cancelled' && $existing->status !== 'cancelled') {
                $this->inventoryReservations->releaseForOrder($orderNumber);
                $this->outbox->append(new OrderCancelled(
                    orderNumber: $orderNumber,
                    previousStatus: $existing->status,
                ));
            }

            return $this->orderingRepository->updateOrderStatus($orderNumber, $newStatus);
        });

        return $updated->toArray();
    }
}
