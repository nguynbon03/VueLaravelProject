<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

final readonly class CustomerSnapshot
{
    public function __construct(
        public ?int $userId,
        public string $name,
        public string $email,
        public string $phone,
        public string $shippingAddress,
        public ?string $shippingProvince = null,
        public ?string $note = null,
    ) {}

    public function formattedAddress(): string
    {
        $addr = trim($this->shippingAddress);
        $prov = $this->shippingProvince !== null ? trim($this->shippingProvince) : '';

        if ($prov !== '' && ! str_contains(mb_strtolower($addr), mb_strtolower($prov))) {
            return sprintf('%s, %s', $addr, $prov);
        }

        return $addr;
    }
}
