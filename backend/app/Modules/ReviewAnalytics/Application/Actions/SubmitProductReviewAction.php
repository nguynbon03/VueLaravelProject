<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Application\Actions;

use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Ordering\Domain\Contracts\OrderAnalyticsReadPort;
use App\Modules\ReviewAnalytics\Application\DTOs\SubmitReviewData;
use App\Modules\ReviewAnalytics\Domain\Contracts\ReviewRepositoryPort;
use DomainException;

final readonly class SubmitProductReviewAction
{
    public function __construct(
        private CatalogVariantReadPort $catalogVariants,
        private OrderAnalyticsReadPort $orderAnalytics,
        private ReviewRepositoryPort $reviews,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(SubmitReviewData $data): array
    {
        $productId = $this->catalogVariants->findProductIdBySlug($data->productSlug);

        if ($productId === null) {
            throw new DomainException('Không tìm thấy sản phẩm.');
        }

        $isVerifiedPurchase = $this->orderAnalytics->hasUserPurchasedProduct(
            $data->userId,
            $productId,
        );

        $review = $this->reviews->createReview(
            productId: $productId,
            userId: $data->userId,
            userName: trim($data->userName),
            rating: $data->rating,
            comment: trim($data->comment),
            isVerifiedPurchase: $isVerifiedPurchase,
        );

        return $review->toArray();
    }
}
