<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Console;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use Illuminate\Console\Command;

final class OutboxRelayCommand extends Command
{
    protected $signature = 'outbox:relay
        {--once : Dispatch pending outbox events and sweep expired reservations once}
        {--daemon : Continuously poll outbox events and expired reservations}
        {--sleep-ms=500 : Milliseconds to sleep between daemon cycles}';

    protected $description = 'Relays pending transactional outbox messages and sweeps expired stock reservations';

    public function handle(
        OutboxPort $outbox,
        InventoryReservationPort $inventoryReservations,
    ): int {
        $daemon = (bool) $this->option('daemon') && ! (bool) $this->option('once');
        $sleepUs = max(50, (int) $this->option('sleep-ms')) * 1000;

        do {
            $dispatched = $outbox->dispatchPending(100);
            $swept = $inventoryReservations->releaseExpiredReservations();

            $this->info(sprintf(
                '[OutboxRelay] Dispatched %d outbox message(s); swept %d expired reservation(s).',
                $dispatched,
                $swept,
            ));

            if ($daemon) {
                usleep($sleepUs);
            }
        } while ($daemon);

        return self::SUCCESS;
    }
}
