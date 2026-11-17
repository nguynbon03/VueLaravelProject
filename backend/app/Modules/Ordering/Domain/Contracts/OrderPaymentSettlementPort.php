<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Contracts;

use App\Modules\Ordering\Domain\Entities\OrderSettlementTarget;

interface OrderPaymentSettlementPort
{
    public function findSettlementTarget(string $orderNumber): ?OrderSettlementTarget;

    public function settlePaidOrder(
        string $orderNumber,
        int $paidAmountMinor,
        string $provider,
        string $transactionRef,
    ): OrderSettlementTarget;
}
