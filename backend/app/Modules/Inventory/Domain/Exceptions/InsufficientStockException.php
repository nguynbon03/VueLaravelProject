<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exceptions;

use DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : sprintf(
                    'Không đủ tồn kho cho biến thể #%d (yêu cầu %d, còn lại %d).',
                    $variantId,
                    $requested,
                    $available,
                )
        );
    }
}
