<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\EndpointEvent;

class FanOutDeliveries
{
    public function handle(EndpointEvent $endpointEvent): array
    {
        $activeDestinations = Destination::query()
            ->where('endpoint_id', $endpointEvent->endpoint_id)
            ->where('is_active', true)
            ->get();

        $deliveryIds = [];

        foreach ($activeDestinations as $destination) {
            $delivery = new Delivery();
            $delivery->endpointEvent()->associate($endpointEvent);
            $delivery->destination()->associate($destination);
            $delivery->status = DeliveryStatus::Pending->value;
            $delivery->attempts = 0;
            $delivery->next_attempt_at = now()->toDateTimeString();
            $delivery->save();

            $deliveryIds[] = $delivery->id;
        }

        return $deliveryIds;
    }
}
