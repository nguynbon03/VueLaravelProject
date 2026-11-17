<?php

declare(strict_types=1);

use App\Core\SharedKernel\Infrastructure\Http\Middleware\AdminRoleMiddleware;
use App\Core\SharedKernel\Infrastructure\Http\Middleware\IdempotencyMiddleware;
use App\Modules\Catalog\UI\Http\Controllers\AdminProductController;
use App\Modules\Catalog\UI\Http\Controllers\CatalogController;
use App\Modules\Identity\UI\Http\Controllers\AdminUserController;
use App\Modules\Identity\UI\Http\Controllers\AuthController;
use App\Modules\Inventory\UI\Http\Controllers\AdminInventoryController;
use App\Modules\Ordering\UI\Http\Controllers\AdminOrderController;
use App\Modules\Ordering\UI\Http\Controllers\CheckoutController;
use App\Modules\Ordering\UI\Http\Controllers\OrderController;
use App\Modules\Payment\UI\Http\Controllers\PaymentWebhookController;
use App\Modules\Promotion\UI\Http\Controllers\CartQuoteController;
use App\Modules\ReviewAnalytics\UI\Http\Controllers\AdminAnalyticsController;
use App\Modules\ReviewAnalytics\UI\Http\Controllers\ProductReviewController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // 1. Health
    Route::get('/health', static fn (): JsonResponse => new JsonResponse([
        'status' => 'ok',
        'architecture' => 'modular-monolith-clean-arch',
        'version' => '1.0.0',
    ], 200));

    // 2. Identity
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    // 3. Catalog & Product Reviews
    Route::get('/catalog/categories', [CatalogController::class, 'categories']);
    Route::get('/catalog/products', [CatalogController::class, 'products']);
    Route::get('/catalog/products/{slug}', [CatalogController::class, 'show']);
    Route::post('/catalog/products/{slug}/reviews', [ProductReviewController::class, 'store'])
        ->middleware('auth:sanctum');

    // 4. Cart Quote & Promotion
    Route::post('/cart/quote', [CartQuoteController::class, 'quote']);

    // 5. Ordering & Payment
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware(IdempotencyMiddleware::class);

    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);

    Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handle']);
    Route::post('/payments/dev-simulate-webhook', [PaymentWebhookController::class, 'simulateDev']);

    // 6. Admin Backoffice
    Route::prefix('admin')
        ->middleware(['auth:sanctum', AdminRoleMiddleware::class])
        ->group(function (): void {
            Route::get('/analytics/overview', [AdminAnalyticsController::class, 'overview']);
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::patch('/orders/{orderNumber}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('/products', [AdminProductController::class, 'store']);
            Route::post('/inventory/adjust', [AdminInventoryController::class, 'adjust']);
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole']);
        });
});
