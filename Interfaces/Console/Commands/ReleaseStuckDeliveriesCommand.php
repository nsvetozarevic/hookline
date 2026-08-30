<?php

declare(strict_types=1);

namespace Interfaces\Console\Commands;

use Domain\Delivery\Actions\ReleaseStuckDeliveries;
use Illuminate\Console\Command;

class ReleaseStuckDeliveriesCommand extends Command
{
    protected $signature = 'hookline:release-stuck-deliveries';

    protected $description = 'Return in-flight deliveries stuck past the timeout to pending';

    public function handle(ReleaseStuckDeliveries $releaseStuckDeliveries): int
    {
        $releasedCount = $releaseStuckDeliveries->handle();

        $this->info(sprintf('Released %d stuck deliveries.', $releasedCount));

        return self::SUCCESS;
    }
}
