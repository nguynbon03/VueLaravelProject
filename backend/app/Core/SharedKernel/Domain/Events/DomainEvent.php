<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Domain\Events;

interface DomainEvent
{
    public function aggregateType(): string;

    public function aggregateId(): string;

    public function eventType(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
