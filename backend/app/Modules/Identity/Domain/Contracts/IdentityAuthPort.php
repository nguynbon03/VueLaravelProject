<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Contracts;

use App\Modules\Identity\Domain\Entities\UserIdentity;

interface IdentityAuthPort
{
    public function emailExists(string $email): bool;

    /**
     * @return array{token: string, user: UserIdentity}
     */
    public function registerCustomer(
        string $name,
        string $email,
        string $plainPassword,
        ?string $phone,
    ): array;

    /**
     * @return array{token: string, user: UserIdentity}|null
     */
    public function attemptLogin(string $email, string $plainPassword): ?array;

    public function findById(int $userId): ?UserIdentity;

    /**
     * @return array<int, array{id: int, name: string, email: string, role: string, phone: string|null, orders_count: int, created_at: string}>
     */
    public function listAllUsersWithStats(): array;

    public function updateRole(int $userId, string $role): ?UserIdentity;
}
