<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Contracts;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Payment\Domain\Entities\PaymentInstruction;

interface PaymentInstructionPort
{
    public function generateInstruction(
        string $paymentMethod,
        string $orderNumber,
        Money $amount,
    ): PaymentInstruction;
}
