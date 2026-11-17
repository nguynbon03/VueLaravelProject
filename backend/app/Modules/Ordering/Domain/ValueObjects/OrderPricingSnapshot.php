<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class OrderPricingSnapshot
{
    public function __construct(
        public Money $subtotal,
        public Money $discount,
        public Money $shippingFee,
        public Money $grandTotal,
        public ?string $couponCode = null,
    ) {}
}
