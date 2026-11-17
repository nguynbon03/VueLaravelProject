<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\RegisterCustomerData;
use App\Modules\Identity\Domain\Contracts\IdentityAuthPort;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;

final readonly class AuthActions
{
    public function __construct(
        private IdentityAuthPort $authPort,
    ) {}

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(RegisterCustomerData $data): array
    {
        $result = $this->authPort->registerCustomer(
            name: trim($data->name),
            email: strtolower(trim($data->email)),
            plainPassword: $data->password,
            phone: $data->phone !== null && trim($data->phone) !== '' ? trim($data->phone) : null,
        );

        return [
            'token' => $result['token'],
            'user' => $result['user']->toArray(),
        ];
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $result = $this->authPort->attemptLogin(strtolower(trim($email)), $password);

        if ($result === null) {
            throw new InvalidCredentialsException();
        }

        return [
            'token' => $result['token'],
            'user' => $result['user']->toArray(),
        ];
    }
}
