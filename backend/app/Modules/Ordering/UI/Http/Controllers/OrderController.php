<?php

declare(strict_types=1);

namespace App\Modules\Ordering\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ordering\Application\Actions\OrderQueryActions;
use App\Modules\Ordering\Domain\Exceptions\UnauthorizedOrderAccessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryActions $orderQueries,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return new JsonResponse([
            'data' => $this->orderQueries->listCustomerOrders((int) $user->id),
        ], 200);
    }

    public function show(string $orderNumber): JsonResponse
    {
        $user = auth('sanctum')->user();

        try {
            $order = $this->orderQueries->getOrderDetail(
                orderNumber: $orderNumber,
                requesterUserId: $user !== null ? (int) $user->id : null,
                requesterRole: $user !== null ? (string) ($user->role ?? 'customer') : null,
            );
        } catch (UnauthorizedOrderAccessException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 403);
        }

        if ($order === null) {
            return new JsonResponse([
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        return new JsonResponse(['data' => $order], 200);
    }
}
