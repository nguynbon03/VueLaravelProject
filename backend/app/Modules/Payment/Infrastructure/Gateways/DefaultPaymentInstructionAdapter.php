<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use App\Modules\Payment\Domain\Contracts\PaymentInstructionPort;
use App\Modules\Payment\Domain\Entities\PaymentInstruction;

final class DefaultPaymentInstructionAdapter implements PaymentInstructionPort
{
    public function generateInstruction(
        string $paymentMethod,
        string $orderNumber,
        Money $amount,
    ): PaymentInstruction {
        $expiresAt = now()->addMinutes(30)->toIso8601String();

        if ($paymentMethod === 'vietqr') {
            $bankCode = (string) config('services.vietqr.bank_code', 'MB');
            $bankName = (string) config('services.vietqr.bank_name', 'MB Bank');
            $accountNumber = (string) config('services.vietqr.account_number', '0333666999');
            $accountHolder = (string) config('services.vietqr.account_holder', 'CONG TY CLEAN COMMERCE');

            $qrUrl = sprintf(
                'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s',
                rawurlencode($bankCode),
                rawurlencode($accountNumber),
                $amount->amountMinor,
                rawurlencode($orderNumber),
            );

            return new PaymentInstruction(
                provider: 'sepay_vietqr',
                qrImageUrl: $qrUrl,
                bankName: $bankName,
                accountNumber: $accountNumber,
                accountHolder: $accountHolder,
                transferContent: $orderNumber,
                amountMinor: $amount->amountMinor,
                expiresAt: $expiresAt,
            );
        }

        if ($paymentMethod === 'stripe') {
            return new PaymentInstruction(
                provider: 'stripe',
                qrImageUrl: null,
                bankName: null,
                accountNumber: null,
                accountHolder: 'CLEAN COMMERCE STRIPE GATEWAY',
                transferContent: $orderNumber,
                amountMinor: $amount->amountMinor,
                expiresAt: $expiresAt,
            );
        }

        return new PaymentInstruction(
            provider: 'cod',
            qrImageUrl: null,
            bankName: null,
            accountNumber: null,
            accountHolder: null,
            transferContent: $orderNumber,
            amountMinor: $amount->amountMinor,
            expiresAt: null,
        );
    }
}
