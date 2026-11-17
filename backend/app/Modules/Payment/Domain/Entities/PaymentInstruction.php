<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entities;

final readonly class PaymentInstruction
{
    public function __construct(
        public string $provider,
        public ?string $qrImageUrl,
        public ?string $bankName,
        public ?string $accountNumber,
        public ?string $accountHolder,
        public string $transferContent,
        public int $amountMinor,
        public ?string $expiresAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'qr_image_url' => $this->qrImageUrl,
            'bank_name' => $this->bankName,
            'account_number' => $this->accountNumber,
            'account_holder' => $this->accountHolder,
            'transfer_content' => $this->transferContent,
            'amount_minor' => $this->amountMinor,
            'expires_at' => $this->expiresAt,
        ];
    }
}
