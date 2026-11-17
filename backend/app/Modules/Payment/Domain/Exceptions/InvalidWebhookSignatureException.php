<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exceptions;

use DomainException;

final class InvalidWebhookSignatureException extends DomainException
{
    public function __construct(string $message = 'Chữ ký HMAC webhook không hợp lệ.')
    {
        parent::__construct($message);
    }
}
