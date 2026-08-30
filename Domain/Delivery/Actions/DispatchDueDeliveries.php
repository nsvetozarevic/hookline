<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;

class DispatchDueDeliveries
{
    public function handle(): int
    {
        $count = 0;

        Delivery::query()
            ->where('status', DeliveryStatus::Pending->value)
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->select('id')
            ->lazyById()
            ->each(function (Delivery $delivery) use (&$count): void {
                DeliverDelivery::dispatch($delivery->id);
                $count++;
            });

        return $count;
    }
}
