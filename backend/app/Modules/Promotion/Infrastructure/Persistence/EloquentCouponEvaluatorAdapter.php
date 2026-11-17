<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Infrastructure\Persistence;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Promotion\Domain\Contracts\CouponEvaluatorPort;
use App\Modules\Promotion\Domain\Entities\CouponEntity;
use App\Modules\Promotion\Domain\Entities\CouponEvaluation;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentCouponEvaluatorAdapter implements CouponEvaluatorPort
{
    private const int BASE_SHIPPING_MINOR = 30000;

    private const int FREE_SHIPPING_THRESHOLD_MINOR = 1000000;

    public function evaluate(?string $couponCode, Money $subtotal): CouponEvaluation
    {
        $normalized = $this->normalizeCode($couponCode);

        if ($normalized === null) {
            return CouponEvaluation::none($subtotal->currency);
        }

        $row = DB::table('promotion_coupons')->where('code', $normalized)->first();

        if ($row === null) {
            return CouponEvaluation::rejected(
                sprintf('Mã giảm giá "%s" không hợp lệ.', $normalized),
                $subtotal->currency,
            );
        }

        return $this->mapEntity($row, $subtotal->currency)->evaluate($subtotal);
    }

    public function redeem(?string $couponCode, Money $subtotal): CouponEvaluation
    {
        $normalized = $this->normalizeCode($couponCode);

        if ($normalized === null) {
            return CouponEvaluation::none($subtotal->currency);
        }

        return DB::transaction(function () use ($normalized, $subtotal): CouponEvaluation {
            $row = DB::table('promotion_coupons')
                ->where('code', $normalized)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new DomainException(sprintf('Mã giảm giá "%s" không tồn tại.', $normalized));
            }

            $evaluation = $this->mapEntity($row, $subtotal->currency)->evaluate($subtotal);

            if ($evaluation->error !== null) {
                throw new DomainException($evaluation->error);
            }

            DB::table('promotion_coupons')
                ->where('id', $row->id)
                ->update([
                    'used_count' => (int) $row->used_count + 1,
                    'updated_at' => now(),
                ]);

            return $evaluation;
        });
    }

    public function calculateShipping(Money $subtotal, bool $freeShippingCoupon): Money
    {
        if ($subtotal->amountMinor === 0) {
            return Money::zero($subtotal->currency);
        }

        if ($freeShippingCoupon || $subtotal->amountMinor >= self::FREE_SHIPPING_THRESHOLD_MINOR) {
            return Money::zero($subtotal->currency);
        }

        return new Money(self::BASE_SHIPPING_MINOR, $subtotal->currency);
    }

    private function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $trimmed = strtoupper(trim($code));

        return $trimmed !== '' ? $trimmed : null;
    }

    private function mapEntity(object $row, string $currency): CouponEntity
    {
        $now = now();
        $startsOk = $row->starts_at === null || $now->gte(Carbon::parse($row->starts_at));
        $expiresOk = $row->expires_at === null || $now->lte(Carbon::parse($row->expires_at));

        return new CouponEntity(
            id: (int) $row->id,
            code: (string) $row->code,
            label: (string) $row->label,
            type: (string) $row->type,
            value: (int) $row->value,
            maxDiscount: $row->max_discount_minor !== null
                ? new Money((int) $row->max_discount_minor, $currency)
                : null,
            minSubtotal: new Money((int) $row->min_subtotal_minor, $currency),
            usageLimit: $row->usage_limit !== null ? (int) $row->usage_limit : null,
            usedCount: (int) $row->used_count,
            isActive: (bool) $row->is_active,
            isWithinWindow: $startsOk && $expiresOk,
        );
    }
}
