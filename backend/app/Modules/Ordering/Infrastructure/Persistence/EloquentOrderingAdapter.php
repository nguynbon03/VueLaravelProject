<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Ordering\Domain\Contracts\OrderAnalyticsReadPort;
use App\Modules\Ordering\Domain\Contracts\OrderPaymentSettlementPort;
use App\Modules\Ordering\Domain\Contracts\OrderingRepositoryPort;
use App\Modules\Ordering\Domain\Entities\OrderEntity;
use App\Modules\Ordering\Domain\Entities\OrderLineEntity;
use App\Modules\Ordering\Domain\Entities\OrderSettlementTarget;
use App\Modules\Ordering\Domain\Events\OrderPaid;
use App\Modules\Ordering\Domain\Events\OrderRefundRequired;
use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;
use App\Modules\Ordering\Domain\ValueObjects\OrderDraft;
use App\Modules\Ordering\Domain\ValueObjects\OrderPricingSnapshot;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class EloquentOrderingAdapter implements
    OrderingRepositoryPort,
    OrderPaymentSettlementPort,
    OrderAnalyticsReadPort
{
    public function __construct(
        private InventoryReservationPort $inventoryReservations,
        private OutboxPort $outbox,
    ) {}

    public function generateUniqueOrderNumber(): string
    {
        $datePart = now()->format('Ymd');

        do {
            $candidate = sprintf('ORD-%s-%s', $datePart, strtoupper(Str::random(4)));
            $exists = DB::table('ordering_orders')->where('order_number', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }

    public function createOrder(OrderDraft $draft): OrderEntity
    {
        $now = now();

        $orderId = (int) DB::table('ordering_orders')->insertGetId([
            'order_number' => $draft->orderNumber,
            'user_id' => $draft->customer->userId,
            'status' => $draft->status,
            'payment_status' => $draft->paymentStatus,
            'payment_method' => $draft->paymentMethod,
            'subtotal_minor' => $draft->pricing->subtotal->amountMinor,
            'discount_minor' => $draft->pricing->discount->amountMinor,
            'shipping_fee_minor' => $draft->pricing->shippingFee->amountMinor,
            'grand_total_minor' => $draft->pricing->grandTotal->amountMinor,
            'currency' => $draft->pricing->grandTotal->currency,
            'coupon_code' => $draft->pricing->couponCode,
            'customer_name' => $draft->customer->name,
            'customer_email' => $draft->customer->email,
            'customer_phone' => $draft->customer->phone,
            'shipping_address' => $draft->customer->shippingAddress,
            'shipping_province' => $draft->customer->shippingProvince,
            'note' => $draft->customer->note,
            'payment_instruction' => json_encode($draft->paymentInstruction, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'paid_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($draft->lines as $line) {
            DB::table('ordering_order_lines')->insert([
                'order_id' => $orderId,
                'product_id' => $line->productId,
                'variant_id' => $line->variantId,
                'sku' => $line->sku,
                'product_name' => $line->productName,
                'variant_label' => $line->variantLabel,
                'quantity' => $line->quantity,
                'unit_price_minor' => $line->unitPrice->amountMinor,
                'line_total_minor' => $line->lineTotal->amountMinor,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return new OrderEntity(
            id: $orderId,
            orderNumber: $draft->orderNumber,
            status: $draft->status,
            paymentStatus: $draft->paymentStatus,
            paymentMethod: $draft->paymentMethod,
            pricing: $draft->pricing,
            customer: $draft->customer,
            createdAtIso: $now->toIso8601String(),
            lines: $draft->lines,
            paymentInstruction: $draft->paymentInstruction,
        );
    }

    public function findByOrderNumber(string $orderNumber): ?OrderEntity
    {
        $rows = DB::table('ordering_orders')
            ->where('order_number', $orderNumber)
            ->limit(1)
            ->get();

        return $this->mapOrders($rows)[0] ?? null;
    }

    public function listByUserId(int $userId): array
    {
        $rows = DB::table('ordering_orders')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->mapOrders($rows);
    }

    public function listAllOrders(): array
    {
        $rows = DB::table('ordering_orders')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->mapOrders($rows);
    }

    public function updateOrderStatus(string $orderNumber, string $newStatus): OrderEntity
    {
        $now = now();
        $updates = [
            'status' => $newStatus,
            'updated_at' => $now,
        ];

        if ($newStatus === 'completed') {
            $existing = DB::table('ordering_orders')->where('order_number', $orderNumber)->first();
            if ($existing !== null && $existing->payment_status !== 'paid') {
                $updates['payment_status'] = 'paid';
                $updates['paid_at'] = $now;
                $this->inventoryReservations->confirmForOrder($orderNumber);
            }
        }

        DB::table('ordering_orders')
            ->where('order_number', $orderNumber)
            ->update($updates);

        $reloaded = $this->findByOrderNumber($orderNumber);

        if ($reloaded === null) {
            throw new DomainException(sprintf('Không tìm thấy đơn hàng %s.', $orderNumber));
        }

        return $reloaded;
    }

    public function findSettlementTarget(string $orderNumber): ?OrderSettlementTarget
    {
        $row = DB::table('ordering_orders')
            ->where('order_number', $orderNumber)
            ->first();

        if ($row === null) {
            return null;
        }

        return new OrderSettlementTarget(
            orderNumber: (string) $row->order_number,
            status: (string) $row->status,
            paymentStatus: (string) $row->payment_status,
            grandTotalMinor: (int) $row->grand_total_minor,
        );
    }

    public function settlePaidOrder(
        string $orderNumber,
        int $paidAmountMinor,
        string $provider,
        string $transactionRef,
    ): OrderSettlementTarget {
        return DB::transaction(function () use ($orderNumber, $paidAmountMinor, $provider, $transactionRef): OrderSettlementTarget {
            $row = DB::table('ordering_orders')
                ->where('order_number', $orderNumber)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new DomainException(sprintf('Không tìm thấy đơn hàng %s.', $orderNumber));
            }

            $expectedMinor = (int) $row->grand_total_minor;
            if ($paidAmountMinor < $expectedMinor) {
                throw new DomainException(sprintf(
                    'Số tiền thanh toán (%d) nhỏ hơn tổng đơn hàng (%d).',
                    $paidAmountMinor,
                    $expectedMinor,
                ));
            }

            if (in_array((string) $row->payment_status, ['paid', 'paid_pending_refund'], true)) {
                return new OrderSettlementTarget(
                    orderNumber: (string) $row->order_number,
                    status: (string) $row->status,
                    paymentStatus: (string) $row->payment_status,
                    grandTotalMinor: $expectedMinor,
                );
            }

            $now = now();
            $stockConfirmed = $this->inventoryReservations->confirmForOrder($orderNumber);

            if (! $stockConfirmed) {
                DB::table('ordering_orders')
                    ->where('id', $row->id)
                    ->update([
                        'status' => 'refund_required',
                        'payment_status' => 'paid_pending_refund',
                        'paid_at' => $now,
                        'updated_at' => $now,
                    ]);

                $this->outbox->append(new OrderRefundRequired(
                    orderNumber: $orderNumber,
                    paidAmountMinor: $paidAmountMinor,
                    provider: $provider,
                    transactionRef: $transactionRef,
                    reason: 'late_payment_after_expired_hold_out_of_stock',
                ));

                return new OrderSettlementTarget(
                    orderNumber: $orderNumber,
                    status: 'refund_required',
                    paymentStatus: 'paid_pending_refund',
                    grandTotalMinor: $expectedMinor,
                );
            }

            $nextStatus = in_array((string) $row->status, ['shipped', 'completed'], true)
                ? (string) $row->status
                : 'paid';

            DB::table('ordering_orders')
                ->where('id', $row->id)
                ->update([
                    'status' => $nextStatus,
                    'payment_status' => 'paid',
                    'paid_at' => $now,
                    'updated_at' => $now,
                ]);

            $this->outbox->append(new OrderPaid(
                orderNumber: $orderNumber,
                paidAmountMinor: $paidAmountMinor,
                provider: $provider,
                transactionRef: $transactionRef,
            ));

            return new OrderSettlementTarget(
                orderNumber: $orderNumber,
                status: $nextStatus,
                paymentStatus: 'paid',
                grandTotalMinor: $expectedMinor,
            );
        });
    }

    public function hasUserPurchasedProduct(int $userId, int $productId): bool
    {
        return DB::table('ordering_orders as o')
            ->join('ordering_order_lines as l', 'l.order_id', '=', 'o.id')
            ->where('o.user_id', $userId)
            ->where('l.product_id', $productId)
            ->where(function ($q): void {
                $q->where('o.payment_status', 'paid')
                    ->orWhereIn('o.status', ['paid', 'completed']);
            })
            ->exists();
    }

    public function getRevenueSummary(): array
    {
        $totalOrdersCount = (int) DB::table('ordering_orders')->count();

        $paidAgg = DB::table('ordering_orders')
            ->selectRaw('COUNT(*) as paid_count, COALESCE(SUM(grand_total_minor), 0) as gmv_minor')
            ->where('payment_status', 'paid')
            ->first();

        $paidCount = $paidAgg !== null ? (int) $paidAgg->paid_count : 0;
        $gmvMinor = $paidAgg !== null ? (int) $paidAgg->gmv_minor : 0;
        $avgMinor = $paidCount > 0 ? intdiv($gmvMinor, $paidCount) : 0;

        return [
            'gmv_minor' => $gmvMinor,
            'paid_orders_count' => $paidCount,
            'total_orders_count' => $totalOrdersCount,
            'avg_order_value_minor' => $avgMinor,
        ];
    }

    /**
     * @param  Collection<int, object>  $orderRows
     * @return array<int, OrderEntity>
     */
    private function mapOrders(Collection $orderRows): array
    {
        if ($orderRows->isEmpty()) {
            return [];
        }

        $orderIds = $orderRows->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $lineRows = DB::table('ordering_order_lines')
            ->whereIn('order_id', $orderIds)
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');

        $orders = [];
        foreach ($orderRows as $row) {
            $orderId = (int) $row->id;
            $currency = (string) ($row->currency ?? 'VND');

            $lines = [];
            foreach ($lineRows->get($orderId, collect()) as $lRow) {
                $lines[] = new OrderLineEntity(
                    productId: (int) $lRow->product_id,
                    variantId: (int) $lRow->variant_id,
                    sku: (string) $lRow->sku,
                    productName: (string) $lRow->product_name,
                    variantLabel: (string) $lRow->variant_label,
                    quantity: (int) $lRow->quantity,
                    unitPrice: new Money((int) $lRow->unit_price_minor, $currency),
                    lineTotal: new Money((int) $lRow->line_total_minor, $currency),
                );
            }

            $instruction = null;
            if ($row->payment_instruction !== null) {
                $decoded = json_decode((string) $row->payment_instruction, true);
                if (is_array($decoded)) {
                    $instruction = $decoded;
                }
            }

            $orders[] = new OrderEntity(
                id: $orderId,
                orderNumber: (string) $row->order_number,
                status: (string) $row->status,
                paymentStatus: (string) $row->payment_status,
                paymentMethod: (string) $row->payment_method,
                pricing: new OrderPricingSnapshot(
                    subtotal: new Money((int) $row->subtotal_minor, $currency),
                    discount: new Money((int) $row->discount_minor, $currency),
                    shippingFee: new Money((int) $row->shipping_fee_minor, $currency),
                    grandTotal: new Money((int) $row->grand_total_minor, $currency),
                    couponCode: $row->coupon_code !== null ? (string) $row->coupon_code : null,
                ),
                customer: new CustomerSnapshot(
                    userId: $row->user_id !== null ? (int) $row->user_id : null,
                    name: (string) $row->customer_name,
                    email: (string) $row->customer_email,
                    phone: (string) $row->customer_phone,
                    shippingAddress: (string) $row->shipping_address,
                    shippingProvince: $row->shipping_province !== null ? (string) $row->shipping_province : null,
                    note: $row->note !== null ? (string) $row->note : null,
                ),
                createdAtIso: Carbon::parse($row->created_at)->toIso8601String(),
                lines: $lines,
                paymentInstruction: $instruction,
            );
        }

        return $orders;
    }
}
