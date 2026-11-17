<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ReviewAnalytics\Application\Actions\SubmitProductReviewAction;
use App\Modules\ReviewAnalytics\Application\DTOs\SubmitReviewData;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductReviewController extends Controller
{
    public function __construct(
        private readonly SubmitProductReviewAction $submitReviewAction,
    ) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:3', 'max:1500'],
        ]);

        $user = $request->user();

        try {
            $review = $this->submitReviewAction->execute(new SubmitReviewData(
                productSlug: $slug,
                userId: (int) $user->id,
                userName: (string) $user->name,
                rating: (int) $validated['rating'],
                comment: (string) $validated['comment'],
            ));
        } catch (DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }

        return new JsonResponse(['data' => $review], 201);
    }
}
