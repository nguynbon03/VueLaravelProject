<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;

final readonly class PlaceOrderCommand
{
    /**
     * @param  array<int, array{variant_id: int, quantity: int}>  $items
     */
    public function __construct(
        public CustomerSnapshot $customer,
        public string $paymentMethod,
        public ?string $couponCode,
        public array $items,
    ) {}
}
