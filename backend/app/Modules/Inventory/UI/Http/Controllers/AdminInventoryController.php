<?php

declare(strict_types=1);

namespace App\Modules\Inventory\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Application\Actions\AdjustInventoryAction;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminInventoryController extends Controller
{
    public function __construct(
        private readonly AdjustInventoryAction $adjustInventoryAction,
    ) {}

    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'min:1'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->adjustInventoryAction->execute(
                variantId: (int) $validated['variant_id'],
                delta: (int) $validated['delta'],
                reason: (string) $validated['reason'],
                actorId: $request->user() !== null ? (int) $request->user()->id : null,
            );
        } catch (DomainException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }

        return new JsonResponse(['data' => $result], 200);
    }
}
