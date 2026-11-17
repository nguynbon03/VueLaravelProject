<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Core\SharedKernel\Infrastructure\Console\OutboxRelayCommand;
use App\Core\SharedKernel\Infrastructure\Outbox\EloquentOutboxAdapter;
use App\Modules\Catalog\Domain\Contracts\CatalogRepositoryPort;
use App\Modules\Catalog\Domain\Contracts\CatalogVariantReadPort;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentCatalogAdapter;
use App\Modules\Identity\Domain\Contracts\IdentityAuthPort;
use App\Modules\Identity\Infrastructure\Persistence\SanctumIdentityAuthAdapter;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Inventory\Domain\Contracts\InventoryStockReadPort;
use App\Modules\Inventory\Infrastructure\Persistence\EloquentInventoryAdapter;
use App\Modules\Ordering\Domain\Contracts\OrderAnalyticsReadPort;
use App\Modules\Ordering\Domain\Contracts\OrderingRepositoryPort;
use App\Modules\Ordering\Domain\Contracts\OrderPaymentSettlementPort;
use App\Modules\Ordering\Infrastructure\Persistence\EloquentOrderingAdapter;
use App\Modules\Payment\Domain\Contracts\PaymentInboxPort;
use App\Modules\Payment\Domain\Contracts\PaymentInstructionPort;
use App\Modules\Payment\Infrastructure\Gateways\DefaultPaymentInstructionAdapter;
use App\Modules\Payment\Infrastructure\Persistence\EloquentPaymentInboxAdapter;
use App\Modules\Promotion\Domain\Contracts\CouponEvaluatorPort;
use App\Modules\Promotion\Infrastructure\Persistence\EloquentCouponEvaluatorAdapter;
use App\Modules\ReviewAnalytics\Domain\Contracts\ProductReviewReadPort;
use App\Modules\ReviewAnalytics\Domain\Contracts\ReviewRepositoryPort;
use App\Modules\ReviewAnalytics\Infrastructure\Persistence\EloquentReviewAdapter;
use Illuminate\Support\ServiceProvider;

final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // SharedKernel Outbox
        $this->app->singleton(OutboxPort::class, EloquentOutboxAdapter::class);

        // Identity Module
        $this->app->singleton(IdentityAuthPort::class, SanctumIdentityAuthAdapter::class);

        // Catalog Module
        $this->app->singleton(EloquentCatalogAdapter::class);
        $this->app->bind(CatalogRepositoryPort::class, EloquentCatalogAdapter::class);
        $this->app->bind(CatalogVariantReadPort::class, EloquentCatalogAdapter::class);

        // Inventory Module
        $this->app->singleton(EloquentInventoryAdapter::class);
        $this->app->bind(InventoryReservationPort::class, EloquentInventoryAdapter::class);
        $this->app->bind(InventoryStockReadPort::class, EloquentInventoryAdapter::class);

        // Promotion Module
        $this->app->singleton(CouponEvaluatorPort::class, EloquentCouponEvaluatorAdapter::class);

        // Ordering Module
        $this->app->singleton(EloquentOrderingAdapter::class);
        $this->app->bind(OrderingRepositoryPort::class, EloquentOrderingAdapter::class);
        $this->app->bind(OrderPaymentSettlementPort::class, EloquentOrderingAdapter::class);
        $this->app->bind(OrderAnalyticsReadPort::class, EloquentOrderingAdapter::class);

        // Payment Module
        $this->app->singleton(PaymentInstructionPort::class, DefaultPaymentInstructionAdapter::class);
        $this->app->singleton(PaymentInboxPort::class, EloquentPaymentInboxAdapter::class);

        // ReviewAnalytics Module
        $this->app->singleton(EloquentReviewAdapter::class);
        $this->app->bind(ProductReviewReadPort::class, EloquentReviewAdapter::class);
        $this->app->bind(ReviewRepositoryPort::class, EloquentReviewAdapter::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OutboxRelayCommand::class,
            ]);
        }
    }
}
