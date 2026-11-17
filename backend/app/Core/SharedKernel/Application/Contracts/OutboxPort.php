<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Application\Contracts;

use App\Core\SharedKernel\Domain\Events\DomainEvent;

interface OutboxPort
{
    public function append(DomainEvent $event): void;

    public function dispatchPending(int $limit = 100): int;

    /**
     * @return array{pending: int, dispatched: int}
     */
    public function stats(): array;
}
