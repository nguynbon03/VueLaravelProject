<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Application\DTOs;

final readonly class SubmitReviewData
{
    public function __construct(
        public string $productSlug,
        public int $userId,
        public string $userName,
        public int $rating,
        public string $comment,
    ) {}
}
