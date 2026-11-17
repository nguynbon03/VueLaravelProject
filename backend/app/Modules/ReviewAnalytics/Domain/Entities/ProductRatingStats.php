<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Domain\Entities;

final readonly class ProductRatingStats
{
    public function __construct(
        public int $productId,
        public float $ratingAvg,
        public int $reviewsCount,
    ) {}
}
