<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Application\Actions;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Inventory\Domain\Contracts\InventoryStockReadPort;
use App\Modules\Promotion\Domain\Contracts\CouponEvaluatorPort;
use DomainException;

final readonly class CalculateCartQuoteAction
{
    public function __construct(
        private CatalogVariantReadPort $catalogVariants,
        private InventoryStockReadPort $inventoryStocks,
        private CouponEvaluatorPort $couponEvaluator,
    ) {}

    /**
     * @param  array<int, array{variant_id: int, quantity: int}>  $items
     * @return array<string, mixed>
     */
    public function execute(array $items, ?string $couponCode = null): array
    {
        if ($items === []) {
            return [
                'lines' => [],
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'shipping_fee_minor' => 0,
                'grand_total_minor' => 0,
                'currency' => 'VND',
                'applied_coupon' => null,
                'coupon_error' => null,
            ];
        }

        $aggregated = [];
        foreach ($items as $item) {
            $vId = (int) $item['variant_id'];
            $qty = (int) $item['quantity'];
            $aggregated[$vId] = ($aggregated[$vId] ?? 0) + $qty;
        }

        $variantIds = array_keys($aggregated);
        $variantMap = $this->catalogVariants->getVariantsByIds($variantIds);
        $stockMap = $this->inventoryStocks->getAvailableStockMap($variantIds);

        $subtotal = Money::zero('VND');
        $lines = [];

        foreach ($aggregated as $variantId => $qty) {
            $variant = $variantMap[$variantId] ?? null;

            if ($variant === null) {
                throw new DomainException(sprintf('Biến thể sản phẩm #%d không tồn tại.', $variantId));
            }

            $lineTotal = $variant->price->multiply($qty);
            $subtotal = $subtotal->add($lineTotal);

            $lines[] = [
                'variant_id' => $variant->variantId,
                'product_name' => $variant->productName,
                'product_slug' => $variant->productSlug,
                'image_url' => $variant->imageUrl,
                'sku' => $variant->sku,
                'color' => $variant->color,
                'size' => $variant->size,
                'unit_price_minor' => $variant->price->amountMinor,
                'quantity' => $qty,
                'line_total_minor' => $lineTotal->amountMinor,
                'available_stock' => $stockMap[$variantId] ?? 0,
            ];
        }

        $evaluation = $this->couponEvaluator->evaluate($couponCode, $subtotal);
        $shippingFee = $this->couponEvaluator->calculateShipping($subtotal, $evaluation->freeShipping);
        $grandTotal = $subtotal->subtractClamped($evaluation->discount)->add($shippingFee);

        return [
            'lines' => $lines,
            'subtotal_minor' => $subtotal->amountMinor,
            'discount_minor' => $evaluation->discount->amountMinor,
            'shipping_fee_minor' => $shippingFee->amountMinor,
            'grand_total_minor' => $grandTotal->amountMinor,
            'currency' => 'VND',
            'applied_coupon' => $evaluation->appliedCode !== null
                ? [
                    'code' => $evaluation->appliedCode,
                    'label' => (string) $evaluation->appliedLabel,
                ]
                : null,
            'coupon_error' => $evaluation->error,
        ];
    }
}
