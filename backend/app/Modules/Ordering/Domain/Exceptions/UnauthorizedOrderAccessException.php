<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Exceptions;

use DomainException;

final class UnauthorizedOrderAccessException extends DomainException
{
    public function __construct(string $message = 'Bạn không có quyền xem đơn hàng của người dùng khác.')
    {
        parent::__construct($message);
    }
}
