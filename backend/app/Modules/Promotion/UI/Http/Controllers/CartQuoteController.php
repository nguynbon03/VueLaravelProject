<?php

declare(strict_types=1);

namespace App\Modules\Promotion\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Promotion\Application\Actions\CalculateCartQuoteAction;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartQuoteController extends Controller
{
    public function __construct(
        private readonly CalculateCartQuoteAction $quoteAction,
    ) {}

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.variant_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $quote = $this->quoteAction->execute(
                items: $validated['items'],
                couponCode: isset($validated['coupon_code']) ? (string) $validated['coupon_code'] : null,
            );
        } catch (DomainException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }

        return new JsonResponse(['data' => $quote], 200);
    }
}
