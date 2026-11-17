<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

final readonly class UserIdentity
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public ?string $phone,
    ) {}

    /**
     * @return array{id: int, name: string, email: string, role: string, phone: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
        ];
    }
}
