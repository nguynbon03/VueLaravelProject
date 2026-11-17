<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Entities;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class CouponEntity
{
    public function __construct(
        public int $id,
        public string $code,
        public string $label,
        public string $type,
        public int $value,
        public ?Money $maxDiscount,
        public Money $minSubtotal,
        public ?int $usageLimit,
        public int $usedCount,
        public bool $isActive,
        public bool $isWithinWindow,
    ) {}

    public function evaluate(Money $subtotal): CouponEvaluation
    {
        if (! $this->isActive || ! $this->isWithinWindow) {
            return CouponEvaluation::rejected('Mã giảm giá đã hết hạn hoặc ngừng hoạt động.', $subtotal->currency);
        }

        if ($this->usageLimit !== null && $this->usedCount >= $this->usageLimit) {
            return CouponEvaluation::rejected('Mã giảm giá đã hết lượt sử dụng.', $subtotal->currency);
        }

        if (! $subtotal->isGreaterThanOrEqual($this->minSubtotal)) {
            return CouponEvaluation::rejected(
                sprintf(
                    'Đơn tối thiểu %sđ để áp dụng mã %s.',
                    number_format($this->minSubtotal->amountMinor, 0, ',', '.'),
                    $this->code,
                ),
                $subtotal->currency,
            );
        }

        if ($this->type === 'freeship') {
            return new CouponEvaluation(
                appliedCode: $this->code,
                appliedLabel: $this->label,
                discount: Money::zero($subtotal->currency),
                freeShipping: true,
                error: null,
            );
        }

        if ($this->type === 'percentage') {
            $raw = $subtotal->percentage($this->value);
            $capped = $this->maxDiscount !== null ? $raw->min($this->maxDiscount) : $raw;

            return new CouponEvaluation(
                appliedCode: $this->code,
                appliedLabel: $this->label,
                discount: $capped->min($subtotal),
                freeShipping: false,
                error: null,
            );
        }

        $fixed = new Money($this->value, $subtotal->currency);

        return new CouponEvaluation(
            appliedCode: $this->code,
            appliedLabel: $this->label,
            discount: $fixed->min($subtotal),
            freeShipping: false,
            error: null,
        );
    }
}
