<?php

declare(strict_types=1);

namespace App\Modules\Ordering\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Modules\Ordering\Application\Actions\PlaceOrderAction;
use App\Modules\Ordering\Application\DTOs\PlaceOrderCommand;
use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly PlaceOrderAction $placeOrderAction,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'string', 'email', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'shipping_address' => ['required', 'string', 'max:400'],
            'shipping_province' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'string', 'in:vietqr,stripe,cod'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $user = auth('sanctum')->user();

        try {
            $order = $this->placeOrderAction->execute(new PlaceOrderCommand(
                customer: new CustomerSnapshot(
                    userId: $user !== null ? (int) $user->id : null,
                    name: (string) $validated['customer_name'],
                    email: (string) $validated['customer_email'],
                    phone: (string) $validated['customer_phone'],
                    shippingAddress: (string) $validated['shipping_address'],
                    shippingProvince: isset($validated['shipping_province']) ? (string) $validated['shipping_province'] : null,
                    note: isset($validated['note']) ? (string) $validated['note'] : null,
                ),
                paymentMethod: (string) $validated['payment_method'],
                couponCode: isset($validated['coupon_code']) ? (string) $validated['coupon_code'] : null,
                items: $validated['items'],
            ));
        } catch (InsufficientStockException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'errors' => [
                    'items' => [$e->getMessage()],
                ],
            ], 422);
        } catch (DomainException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }

        return new JsonResponse(['data' => $order], 201);
    }
}
