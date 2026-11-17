<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Actions;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Ordering\Application\DTOs\PlaceOrderCommand;
use App\Modules\Ordering\Domain\Contracts\OrderingRepositoryPort;
use App\Modules\Ordering\Domain\Entities\OrderLineEntity;
use App\Modules\Ordering\Domain\Events\OrderPlaced;
use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;
use App\Modules\Ordering\Domain\ValueObjects\OrderDraft;
use App\Modules\Ordering\Domain\ValueObjects\OrderPricingSnapshot;
use App\Modules\Payment\Domain\Contracts\PaymentInstructionPort;
use App\Modules\Promotion\Domain\Contracts\CouponEvaluatorPort;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class PlaceOrderAction
{
    public function __construct(
        private CatalogVariantReadPort $catalogVariants,
        private InventoryReservationPort $inventoryReservations,
        private CouponEvaluatorPort $couponEvaluator,
        private PaymentInstructionPort $paymentInstructions,
        private OrderingRepositoryPort $orderingRepository,
        private OutboxPort $outbox,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(PlaceOrderCommand $command): array
    {
        $order = DB::transaction(function () use ($command) {
            $aggregated = [];
            foreach ($command->items as $item) {
                $vId = (int) $item['variant_id'];
                $qty = (int) $item['quantity'];
                $aggregated[$vId] = ($aggregated[$vId] ?? 0) + $qty;
            }

            if ($aggregated === []) {
                throw new DomainException('Giỏ hàng không được để trống.');
            }

            $variantMap = $this->catalogVariants->getVariantsByIds(array_keys($aggregated));

            $subtotal = Money::zero('VND');
            $orderLines = [];

            foreach ($aggregated as $variantId => $qty) {
                $variant = $variantMap[$variantId] ?? null;

                if ($variant === null) {
                    throw new DomainException(sprintf('Biến thể sản phẩm #%d không tồn tại.', $variantId));
                }

                $lineTotal = $variant->price->multiply($qty);
                $subtotal = $subtotal->add($lineTotal);

                $orderLines[] = new OrderLineEntity(
                    productId: $variant->productId,
                    variantId: $variant->variantId,
                    sku: $variant->sku,
                    productName: $variant->productName,
                    variantLabel: $variant->variantLabel(),
                    quantity: $qty,
                    unitPrice: $variant->price,
                    lineTotal: $lineTotal,
                );
            }

            $evaluation = $this->couponEvaluator->redeem($command->couponCode, $subtotal);
            $shippingFee = $this->couponEvaluator->calculateShipping($subtotal, $evaluation->freeShipping);
            $grandTotal = $subtotal->subtractClamped($evaluation->discount)->add($shippingFee);

            $orderNumber = $this->orderingRepository->generateUniqueOrderNumber();

            $this->inventoryReservations->reserveForOrder($orderNumber, $aggregated, 30);

            $instruction = $this->paymentInstructions->generateInstruction(
                paymentMethod: $command->paymentMethod,
                orderNumber: $orderNumber,
                amount: $grandTotal,
            );

            $initialStatus = $command->paymentMethod === 'cod' ? 'processing' : 'pending_payment';

            $customerSnapshot = new CustomerSnapshot(
                userId: $command->customer->userId,
                name: trim($command->customer->name),
                email: strtolower(trim($command->customer->email)),
                phone: trim($command->customer->phone),
                shippingAddress: $command->customer->formattedAddress(),
                shippingProvince: $command->customer->shippingProvince,
                note: $command->customer->note,
            );

            $pricingSnapshot = new OrderPricingSnapshot(
                subtotal: $subtotal,
                discount: $evaluation->discount,
                shippingFee: $shippingFee,
                grandTotal: $grandTotal,
                couponCode: $evaluation->appliedCode,
            );

            $createdOrder = $this->orderingRepository->createOrder(new OrderDraft(
                orderNumber: $orderNumber,
                status: $initialStatus,
                paymentStatus: 'unpaid',
                paymentMethod: $command->paymentMethod,
                pricing: $pricingSnapshot,
                customer: $customerSnapshot,
                lines: $orderLines,
                paymentInstruction: $instruction->toArray(),
            ));

            $this->outbox->append(new OrderPlaced(
                orderNumber: $createdOrder->orderNumber,
                userId: $createdOrder->customer->userId,
                grandTotalMinor: $grandTotal->amountMinor,
                currency: $grandTotal->currency,
                paymentMethod: $command->paymentMethod,
            ));

            return $createdOrder;
        });

        return $order->toArray();
    }
}
