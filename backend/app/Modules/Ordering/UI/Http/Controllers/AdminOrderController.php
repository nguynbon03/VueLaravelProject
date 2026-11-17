<?php

declare(strict_types=1);

namespace App\Modules\Ordering\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ordering\Application\Actions\OrderQueryActions;
use App\Modules\Ordering\Application\Actions\UpdateAdminOrderStatusAction;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryActions $orderQueries,
        private readonly UpdateAdminOrderStatusAction $updateStatusAction,
    ) {}

    public function index(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->orderQueries->listAdminOrders(),
        ], 200);
    }

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:paid,processing,shipped,completed,cancelled'],
        ]);

        try {
            $updated = $this->updateStatusAction->execute(
                orderNumber: $orderNumber,
                newStatus: (string) $validated['status'],
            );
        } catch (DomainException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        }

        return new JsonResponse(['data' => $updated], 200);
    }
}
