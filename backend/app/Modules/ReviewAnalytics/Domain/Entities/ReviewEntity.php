<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Domain\Entities;

final readonly class ReviewEntity
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $userName,
        public int $rating,
        public string $comment,
        public bool $isVerifiedPurchase,
        public string $createdAt,
    ) {}

    /**
     * @return array{id: int, user_name: string, rating: int, comment: string, is_verified_purchase: bool, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->userName,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified_purchase' => $this->isVerifiedPurchase,
            'created_at' => $this->createdAt,
        ];
    }
}
