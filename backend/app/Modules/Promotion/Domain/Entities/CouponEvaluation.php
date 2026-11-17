<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Entities;

use App\Core\SharedKernel\Domain\ValueObjects\Money;

final readonly class CouponEvaluation
{
    public function __construct(
        public ?string $appliedCode,
        public ?string $appliedLabel,
        public Money $discount,
        public bool $freeShipping,
        public ?string $error,
    ) {}

    public static function none(string $currency = 'VND'): self
    {
        return new self(
            appliedCode: null,
            appliedLabel: null,
            discount: Money::zero($currency),
            freeShipping: false,
            error: null,
        );
    }

    public static function rejected(string $error, string $currency = 'VND'): self
    {
        return new self(
            appliedCode: null,
            appliedLabel: null,
            discount: Money::zero($currency),
            freeShipping: false,
            error: $error,
        );
    }
}
