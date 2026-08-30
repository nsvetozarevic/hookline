<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;

class ReleaseStuckDeliveries
{
    public function handle(): int
    {
        $stuckBefore = now()->subSeconds((int) config('hookline.delivery.in_flight_timeout_seconds'));

        return Delivery::query()
            ->where('status', DeliveryStatus::InFlight->value)
            ->where('locked_at', '<=', $stuckBefore)
            ->update([
                'status' => DeliveryStatus::Pending->value,
                'locked_at' => null,
                'next_attempt_at' => now(),
            ]);
    }
}
