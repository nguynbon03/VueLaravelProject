<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Outbox;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Core\SharedKernel\Domain\Events\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentOutboxAdapter implements OutboxPort
{
    public function append(DomainEvent $event): void
    {
        $now = now();

        DB::table('outbox_messages')->insert([
            'id' => (string) Str::uuid(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'event_type' => $event->eventType(),
            'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'dispatched_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function dispatchPending(int $limit = 100): int
    {
        return DB::transaction(function () use ($limit): int {
            $ids = DB::table('outbox_messages')
                ->whereNull('dispatched_at')
                ->orderBy('created_at')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($ids === []) {
                return 0;
            }

            $now = now();

            return DB::table('outbox_messages')
                ->whereIn('id', $ids)
                ->update([
                    'dispatched_at' => $now,
                    'updated_at' => $now,
                ]);
        });
    }

    public function stats(): array
    {
        $pending = DB::table('outbox_messages')->whereNull('dispatched_at')->count();
        $dispatched = DB::table('outbox_messages')->whereNotNull('dispatched_at')->count();

        return [
            'pending' => (int) $pending,
            'dispatched' => (int) $dispatched,
        ];
    }
}
