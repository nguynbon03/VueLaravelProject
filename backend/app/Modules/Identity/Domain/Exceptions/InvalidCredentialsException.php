<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Email hoặc mật khẩu không chính xác.')
    {
        parent::__construct($message);
    }
}
