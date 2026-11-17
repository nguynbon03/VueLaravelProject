<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Services;

use App\Modules\Payment\Domain\Exceptions\InvalidWebhookSignatureException;

final readonly class HmacSignatureVerifier
{
    public function sign(string $rawPayload, string $secret): string
    {
        return hash_hmac('sha256', $rawPayload, $secret);
    }

    public function verify(string $rawPayload, string $providedSignature, string $secret): void
    {
        if (trim($secret) === '' || trim($providedSignature) === '') {
            throw new InvalidWebhookSignatureException;
        }

        $normalized = strtolower(trim($providedSignature));
        if (str_starts_with($normalized, 'sha256=')) {
            $normalized = substr($normalized, 7);
        }

        $expected = $this->sign($rawPayload, $secret);

        if (! hash_equals($expected, $normalized)) {
            throw new InvalidWebhookSignatureException;
        }
    }
}
