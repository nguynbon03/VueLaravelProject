<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Inventory\Domain\Contracts\InventoryStockReadPort;
use App\Modules\Inventory\Domain\Entities\StockItem;
use App\Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

final class EloquentInventoryAdapter implements InventoryReservationPort, InventoryStockReadPort
{
    public function getAvailableStockMap(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        $rows = DB::table('inventory_stocks')
            ->whereIn('variant_id', array_values(array_unique($variantIds)))
            ->pluck('available_stock', 'variant_id');

        $map = [];
        foreach ($variantIds as $variantId) {
            $map[(int) $variantId] = isset($rows[$variantId]) ? (int) $rows[$variantId] : 0;
        }

        return $map;
    }

    public function getLowStockItems(int $limit = 10): array
    {
        $rows = DB::table('inventory_stocks')
            ->whereColumn('available_stock', '<=', 'low_stock_threshold')
            ->orderBy('available_stock')
            ->orderBy('variant_id')
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = new StockItem(
                variantId: (int) $row->variant_id,
                availableStock: (int) $row->available_stock,
                reservedStock: (int) $row->reserved_stock,
                soldStock: (int) $row->sold_stock,
                lowStockThreshold: (int) $row->low_stock_threshold,
            );
        }

        return $items;
    }

    public function initializeStock(int $variantId, int $initialStock, string $reason = 'initial_stock'): void
    {
        DB::transaction(function () use ($variantId, $initialStock, $reason): void {
            $now = now();
            $safeStock = max(0, $initialStock);

            DB::table('inventory_stocks')->updateOrInsert(
                ['variant_id' => $variantId],
                [
                    'available_stock' => $safeStock,
                    'reserved_stock' => 0,
                    'sold_stock' => 0,
                    'low_stock_threshold' => 5,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('inventory_movements')->insert([
                'variant_id' => $variantId,
                'delta' => $safeStock,
                'balance_after' => $safeStock,
                'reason' => $reason,
                'reference_type' => 'init',
                'reference_id' => (string) $variantId,
                'actor_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function reserveForOrder(string $orderNumber, array $variantQuantities, int $ttlMinutes = 30): void
    {
        if ($variantQuantities === []) {
            return;
        }

        DB::transaction(function () use ($orderNumber, $variantQuantities, $ttlMinutes): void {
            ksort($variantQuantities);
            $variantIds = array_map('intval', array_keys($variantQuantities));

            $lockedRows = DB::table('inventory_stocks')
                ->whereIn('variant_id', $variantIds)
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('variant_id');

            foreach ($variantQuantities as $variantId => $qty) {
                $vId = (int) $variantId;
                $requested = (int) $qty;
                $row = $lockedRows->get($vId);
                $available = $row !== null ? (int) $row->available_stock : 0;

                if ($row === null || $available < $requested) {
                    throw new InsufficientStockException($vId, $requested, $available);
                }
            }

            $now = now();
            $expiresAt = $now->copy()->addMinutes($ttlMinutes);

            foreach ($variantQuantities as $variantId => $qty) {
                $vId = (int) $variantId;
                $requested = (int) $qty;
                $row = $lockedRows->get($vId);
                $newAvailable = (int) $row->available_stock - $requested;
                $newReserved = (int) $row->reserved_stock + $requested;

                DB::table('inventory_stocks')
                    ->where('id', $row->id)
                    ->update([
                        'available_stock' => $newAvailable,
                        'reserved_stock' => $newReserved,
                        'updated_at' => $now,
                    ]);

                DB::table('inventory_reservations')->insert([
                    'order_number' => $orderNumber,
                    'variant_id' => $vId,
                    'quantity' => $requested,
                    'status' => 'reserved',
                    'expires_at' => $expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('inventory_movements')->insert([
                    'variant_id' => $vId,
                    'delta' => -$requested,
                    'balance_after' => $newAvailable,
                    'reason' => sprintf('Giữ tồn kho cho đơn hàng %s', $orderNumber),
                    'reference_type' => 'order_reserve',
                    'reference_id' => $orderNumber,
                    'actor_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function confirmForOrder(string $orderNumber): bool
    {
        return DB::transaction(function () use ($orderNumber): bool {
            $allReservations = DB::table('inventory_reservations')
                ->where('order_number', $orderNumber)
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get();

            if ($allReservations->isEmpty()) {
                return false;
            }

            if ($allReservations->every(static fn ($r): bool => $r->status === 'confirmed')) {
                return true;
            }

            $now = now();
            $activeReserved = $allReservations->filter(static fn ($r): bool => $r->status === 'reserved');

            if ($activeReserved->count() === $allReservations->count()) {
                foreach ($activeReserved as $res) {
                    $stock = DB::table('inventory_stocks')
                        ->where('variant_id', $res->variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock !== null) {
                        $qty = (int) $res->quantity;
                        DB::table('inventory_stocks')
                            ->where('id', $stock->id)
                            ->update([
                                'reserved_stock' => max(0, (int) $stock->reserved_stock - $qty),
                                'sold_stock' => (int) $stock->sold_stock + $qty,
                                'updated_at' => $now,
                            ]);
                    }

                    DB::table('inventory_reservations')
                        ->where('id', $res->id)
                        ->update([
                            'status' => 'confirmed',
                            'updated_at' => $now,
                        ]);
                }

                return true;
            }

            // Edge Case (Late Payment after TTL Expiry / Cancel): Attempt safe atomic re-allocation from live stock
            $toReallocate = $allReservations->filter(static fn ($r): bool => in_array($r->status, ['expired', 'released'], true));
            $variantIds = $toReallocate->pluck('variant_id')->map(static fn ($v): int => (int) $v)->unique()->sort()->values()->all();

            $lockedStocks = DB::table('inventory_stocks')
                ->whereIn('variant_id', $variantIds)
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('variant_id');

            foreach ($toReallocate as $res) {
                $stock = $lockedStocks->get((int) $res->variant_id);
                if ($stock === null || (int) $stock->available_stock < (int) $res->quantity) {
                    // Stock was already sold to another customer after hold expiry -> prevent phantom oversell
                    return false;
                }
            }

            foreach ($toReallocate as $res) {
                $vId = (int) $res->variant_id;
                $qty = (int) $res->quantity;
                $stock = $lockedStocks->get($vId);
                $newAvailable = (int) $stock->available_stock - $qty;

                DB::table('inventory_stocks')
                    ->where('id', $stock->id)
                    ->update([
                        'available_stock' => $newAvailable,
                        'sold_stock' => (int) $stock->sold_stock + $qty,
                        'updated_at' => $now,
                    ]);

                DB::table('inventory_reservations')
                    ->where('id', $res->id)
                    ->update([
                        'status' => 'confirmed',
                        'updated_at' => $now,
                    ]);

                DB::table('inventory_movements')->insert([
                    'variant_id' => $vId,
                    'delta' => -$qty,
                    'balance_after' => $newAvailable,
                    'reason' => sprintf('Cấp phát lại kho cho thanh toán trễ đơn %s', $orderNumber),
                    'reference_type' => 'late_payment_realloc',
                    'reference_id' => $orderNumber,
                    'actor_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return true;
        });
    }

    public function releaseForOrder(string $orderNumber): void
    {
        DB::transaction(function () use ($orderNumber): void {
            $reservations = DB::table('inventory_reservations')
                ->where('order_number', $orderNumber)
                ->whereIn('status', ['reserved', 'confirmed'])
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get();

            if ($reservations->isEmpty()) {
                return;
            }

            $now = now();

            foreach ($reservations as $res) {
                $stock = DB::table('inventory_stocks')
                    ->where('variant_id', $res->variant_id)
                    ->lockForUpdate()
                    ->first();

                $qty = (int) $res->quantity;

                if ($stock !== null) {
                    $newAvailable = (int) $stock->available_stock + $qty;
                    $newReserved = $res->status === 'reserved'
                        ? max(0, (int) $stock->reserved_stock - $qty)
                        : (int) $stock->reserved_stock;
                    $newSold = $res->status === 'confirmed'
                        ? max(0, (int) $stock->sold_stock - $qty)
                        : (int) $stock->sold_stock;

                    DB::table('inventory_stocks')
                        ->where('id', $stock->id)
                        ->update([
                            'available_stock' => $newAvailable,
                            'reserved_stock' => $newReserved,
                            'sold_stock' => $newSold,
                            'updated_at' => $now,
                        ]);

                    DB::table('inventory_movements')->insert([
                        'variant_id' => (int) $res->variant_id,
                        'delta' => $qty,
                        'balance_after' => $newAvailable,
                        'reason' => sprintf('Hoàn kho do huỷ đơn %s', $orderNumber),
                        'reference_type' => 'order_cancel',
                        'reference_id' => $orderNumber,
                        'actor_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('inventory_reservations')
                    ->where('id', $res->id)
                    ->update([
                        'status' => 'released',
                        'updated_at' => $now,
                    ]);
            }
        });
    }

    public function releaseExpiredReservations(): int
    {
        return DB::transaction(function (): int {
            $now = now();

            $expired = DB::table('inventory_reservations')
                ->where('status', 'reserved')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($expired->isEmpty()) {
                return 0;
            }

            foreach ($expired as $res) {
                $stock = DB::table('inventory_stocks')
                    ->where('variant_id', $res->variant_id)
                    ->lockForUpdate()
                    ->first();

                $qty = (int) $res->quantity;

                if ($stock !== null) {
                    $newAvailable = (int) $stock->available_stock + $qty;
                    $newReserved = max(0, (int) $stock->reserved_stock - $qty);

                    DB::table('inventory_stocks')
                        ->where('id', $stock->id)
                        ->update([
                            'available_stock' => $newAvailable,
                            'reserved_stock' => $newReserved,
                            'updated_at' => $now,
                        ]);

                    DB::table('inventory_movements')->insert([
                        'variant_id' => (int) $res->variant_id,
                        'delta' => $qty,
                        'balance_after' => $newAvailable,
                        'reason' => sprintf('Thu hồi giữ chỗ quá hạn đơn %s', $res->order_number),
                        'reference_type' => 'reservation_expired',
                        'reference_id' => (string) $res->order_number,
                        'actor_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('inventory_reservations')
                    ->where('id', $res->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => $now,
                    ]);
            }

            return $expired->count();
        });
    }

    public function adjustStock(int $variantId, int $delta, string $reason, ?int $actorId = null): StockItem
    {
        return DB::transaction(function () use ($variantId, $delta, $reason, $actorId): StockItem {
            $now = now();

            $stock = DB::table('inventory_stocks')
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($stock === null) {
                DB::table('inventory_stocks')->insert([
                    'variant_id' => $variantId,
                    'available_stock' => 0,
                    'reserved_stock' => 0,
                    'sold_stock' => 0,
                    'low_stock_threshold' => 5,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $stock = DB::table('inventory_stocks')
                    ->where('variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();
            }

            $currentAvailable = (int) $stock->available_stock;
            $newAvailable = $currentAvailable + $delta;

            if ($newAvailable < 0) {
                throw new InsufficientStockException(
                    $variantId,
                    abs($delta),
                    $currentAvailable,
                    'Không thể trừ kho vượt quá số lượng khả dụng hiện tại.',
                );
            }

            DB::table('inventory_stocks')
                ->where('id', $stock->id)
                ->update([
                    'available_stock' => $newAvailable,
                    'updated_at' => $now,
                ]);

            DB::table('inventory_movements')->insert([
                'variant_id' => $variantId,
                'delta' => $delta,
                'balance_after' => $newAvailable,
                'reason' => $reason,
                'reference_type' => 'admin_adjust',
                'reference_id' => null,
                'actor_id' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return new StockItem(
                variantId: $variantId,
                availableStock: $newAvailable,
                reservedStock: (int) $stock->reserved_stock,
                soldStock: (int) $stock->sold_stock,
                lowStockThreshold: (int) $stock->low_stock_threshold,
            );
        });
    }
}
