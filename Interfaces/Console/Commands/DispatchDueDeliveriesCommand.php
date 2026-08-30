<?php

declare(strict_types=1);

namespace Interfaces\Console\Commands;

use Domain\Delivery\Actions\DispatchDueDeliveries;
use Illuminate\Console\Command;

class DispatchDueDeliveriesCommand extends Command
{
    protected $signature = 'hookline:dispatch-due-deliveries';

    protected $description = 'Dispatch queue jobs for pending deliveries that are due';

    public function handle(DispatchDueDeliveries $dispatchDueDeliveries): int
    {
        $dispatchedCount = $dispatchDueDeliveries->handle();

        $this->info(sprintf('Dispatched %d due deliveries.', $dispatchedCount));

        return self::SUCCESS;
    }
}
