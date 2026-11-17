<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\Infrastructure\Persistence;

use App\Modules\ReviewAnalytics\Domain\Contracts\ProductReviewReadPort;
use App\Modules\ReviewAnalytics\Domain\Contracts\ReviewRepositoryPort;
use App\Modules\ReviewAnalytics\Domain\Entities\ProductRatingStats;
use App\Modules\ReviewAnalytics\Domain\Entities\ReviewEntity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentReviewAdapter implements ProductReviewReadPort, ReviewRepositoryPort
{
    public function getRatingStatsForProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('catalog_reviews')
            ->selectRaw('product_id, AVG(rating) as rating_avg, COUNT(*) as reviews_count')
            ->whereIn('product_id', array_values(array_unique($productIds)))
            ->groupBy('product_id')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $pId = (int) $row->product_id;
            $stats[$pId] = new ProductRatingStats(
                productId: $pId,
                ratingAvg: round((float) $row->rating_avg, 1),
                reviewsCount: (int) $row->reviews_count,
            );
        }

        return $stats;
    }

    public function getReviewsForProduct(int $productId): array
    {
        $rows = DB::table('catalog_reviews')
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $reviews = [];
        foreach ($rows as $row) {
            $reviews[] = new ReviewEntity(
                id: (int) $row->id,
                productId: (int) $row->product_id,
                userName: (string) $row->user_name,
                rating: (int) $row->rating,
                comment: (string) $row->comment,
                isVerifiedPurchase: (bool) $row->is_verified_purchase,
                createdAt: Carbon::parse($row->created_at)->toIso8601String(),
            );
        }

        return $reviews;
    }

    public function createReview(
        int $productId,
        int $userId,
        string $userName,
        int $rating,
        string $comment,
        bool $isVerifiedPurchase,
    ): ReviewEntity {
        $now = now();

        $id = (int) DB::table('catalog_reviews')->insertGetId([
            'product_id' => $productId,
            'user_id' => $userId,
            'user_name' => $userName,
            'rating' => $rating,
            'comment' => $comment,
            'is_verified_purchase' => $isVerifiedPurchase,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return new ReviewEntity(
            id: $id,
            productId: $productId,
            userName: $userName,
            rating: $rating,
            comment: $comment,
            isVerifiedPurchase: $isVerifiedPurchase,
            createdAt: $now->toIso8601String(),
        );
    }
}
