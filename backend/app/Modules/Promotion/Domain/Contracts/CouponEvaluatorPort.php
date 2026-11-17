<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Contracts;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Promotion\Domain\Entities\CouponEvaluation;

interface CouponEvaluatorPort
{
    public function evaluate(?string $couponCode, Money $subtotal): CouponEvaluation;

    public function redeem(?string $couponCode, Money $subtotal): CouponEvaluation;

    public function calculateShipping(Money $subtotal, bool $freeShippingCoupon): Money;
}
