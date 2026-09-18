<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Support\Facades\Log;

class ReplayDelivery
{
    public function handle(Delivery $delivery): bool
    {
        $replayed = Delivery::query()
            ->whereKey($delivery->id)
            ->whereIn('status', DeliveryStatus::replayableValues())
            ->update([
                'status' => DeliveryStatus::Pending->value,
                'attempts' => 0,
                'locked_at' => null,
                'next_attempt_at' => now()->toDateTimeString(),
                'last_status_code' => null,
                'last_error' => null,
            ]) === 1;

        if (! $replayed) {
            return false;
        }

        $delivery->refresh();

        DeliverDelivery::dispatch($delivery->id);

        Log::channel('hookline')->info('Delivery replayed.', [
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->endpoint_event_id,
            'destination_id' => $delivery->destination_id,
        ]);

        return true;
    }
}
