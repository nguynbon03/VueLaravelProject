<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Domain\Contracts;

use App\Modules\ReviewAnalytics\Domain\Entities\ProductRatingStats;
use App\Modules\ReviewAnalytics\Domain\Entities\ReviewEntity;

interface ProductReviewReadPort
{
    /**
     * @param  array<int, int>  $productIds
     * @return array<int, ProductRatingStats>
     */
    public function getRatingStatsForProducts(array $productIds): array;

    /**
     * @return array<int, ReviewEntity>
     */
    public function getReviewsForProduct(int $productId): array;
}
