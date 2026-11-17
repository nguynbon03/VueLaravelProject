<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Domain\Contracts;

use App\Modules\ReviewAnalytics\Domain\Entities\ReviewEntity;

interface ReviewRepositoryPort
{
    public function createReview(
        int $productId,
        int $userId,
        string $userName,
        int $rating,
        string $comment,
        bool $isVerifiedPurchase,
    ): ReviewEntity;
}
