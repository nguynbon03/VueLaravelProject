<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Models\User;
use App\Modules\Identity\Domain\Contracts\IdentityAuthPort;
use App\Modules\Identity\Domain\Entities\UserIdentity;
use Illuminate\Support\Facades\Hash;

final class SanctumIdentityAuthAdapter implements IdentityAuthPort
{
    public function emailExists(string $email): bool
    {
        return User::query()->where('email', strtolower(trim($email)))->exists();
    }

    public function registerCustomer(
        string $name,
        string $email,
        string $plainPassword,
        ?string $phone,
    ): array {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'role' => 'customer',
            'phone' => $phone,
        ]);

        $token = $user->createToken('spa-auth')->plainTextToken;

        return [
            'token' => $token,
            'user' => $this->toEntity($user),
        ];
    }

    public function attemptLogin(string $email, string $plainPassword): ?array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($plainPassword, (string) $user->password)) {
            return null;
        }

        $token = $user->createToken('spa-auth')->plainTextToken;

        return [
            'token' => $token,
            'user' => $this->toEntity($user),
        ];
    }

    public function findById(int $userId): ?UserIdentity
    {
        $user = User::query()->find($userId);

        return $user !== null ? $this->toEntity($user) : null;
    }

    private function toEntity(User $user): UserIdentity
    {
        return new UserIdentity(
            id: (int) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            role: (string) ($user->role ?? 'customer'),
            phone: $user->phone !== null ? (string) $user->phone : null,
        );
    }
}
