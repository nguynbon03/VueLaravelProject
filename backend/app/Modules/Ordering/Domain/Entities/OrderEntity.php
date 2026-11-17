<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Entities;

use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;
use App\Modules\Ordering\Domain\ValueObjects\OrderPricingSnapshot;

final readonly class OrderEntity
{
    /**
     * @param  array<int, OrderLineEntity>  $lines
     * @param  array<string, mixed>|null  $paymentInstruction
     */
    public function __construct(
        public int $id,
        public string $orderNumber,
        public string $status,
        public string $paymentStatus,
        public string $paymentMethod,
        public OrderPricingSnapshot $pricing,
        public CustomerSnapshot $customer,
        public string $createdAtIso,
        public array $lines,
        public ?array $paymentInstruction = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'subtotal_minor' => $this->pricing->subtotal->amountMinor,
            'discount_minor' => $this->pricing->discount->amountMinor,
            'shipping_fee_minor' => $this->pricing->shippingFee->amountMinor,
            'grand_total_minor' => $this->pricing->grandTotal->amountMinor,
            'currency' => $this->pricing->grandTotal->currency,
            'coupon_code' => $this->pricing->couponCode,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'customer_phone' => $this->customer->phone,
            'shipping_address' => $this->customer->shippingAddress,
            'shipping_province' => $this->customer->shippingProvince,
            'note' => $this->customer->note,
            'created_at' => $this->createdAtIso,
            'lines' => array_map(
                static fn (OrderLineEntity $line): array => $line->toArray(),
                $this->lines,
            ),
            'payment_instruction' => $this->paymentInstruction,
        ];
    }
}
