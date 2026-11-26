<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Outbox;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Core\SharedKernel\Domain\Events\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
            $driver = DB::connection()->getDriverName();
            $query = DB::table('outbox_messages')
                ->whereNull('dispatched_at')
                ->orderBy('created_at')
                ->limit($limit);

            $rows = in_array($driver, ['pgsql', 'mysql', 'mariadb'], true)
                ? $query->lock('FOR UPDATE SKIP LOCKED')->get()
                : $query->lockForUpdate()->get();

            if ($rows->isEmpty()) {
                return 0;
            }

            $dispatchedIds = [];
            foreach ($rows as $row) {
                try {
                    $decoded = is_string($row->payload)
                        ? json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR)
                        : [];

                    Event::dispatch((string) $row->event_type, [[
                        'id' => (string) $row->id,
                        'aggregate_type' => (string) $row->aggregate_type,
                        'aggregate_id' => (string) $row->aggregate_id,
                        'event_type' => (string) $row->event_type,
                        'payload' => is_array($decoded) ? $decoded : [],
                    ]]);

                    $dispatchedIds[] = (string) $row->id;
                } catch (Throwable $e) {
                    Log::error('Outbox event dispatch failed', [
                        'outbox_id' => $row->id ?? null,
                        'event_type' => $row->event_type ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($dispatchedIds === []) {
                return 0;
            }

            $now = now();

            return DB::table('outbox_messages')
                ->whereIn('id', $dispatchedIds)
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
