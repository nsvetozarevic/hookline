<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Support\Facades\Log;

class ReplayDelivery
{
    public function handle(Delivery $delivery): void
    {
        $delivery->status = DeliveryStatus::Pending->value;
        $delivery->attempts = 0;
        $delivery->locked_at = null;
        $delivery->next_attempt_at = now()->toDateTimeString();
        $delivery->last_status_code = null;
        $delivery->last_error = null;
        $delivery->save();

        DeliverDelivery::dispatch($delivery->id);

        Log::channel('hookline')->info('Delivery replayed.', [
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->endpoint_event_id,
            'destination_id' => $delivery->destination_id,
        ]);
    }
}
