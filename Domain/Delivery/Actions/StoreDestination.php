<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Data\StoreDestinationData;
use Domain\Delivery\Models\Destination;
use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Support\Facades\DB;

class StoreDestination
{
    public function handle(StoreDestinationData $storeDestinationData): Destination
    {
        return DB::transaction(function () use ($storeDestinationData): Destination {
            $destination = new Destination();
            $destination->endpoint_id = $storeDestinationData->endpointId;
            $destination->url = $storeDestinationData->url;
            $destination->is_active = true;
            $destination->timeout_seconds = (int) config('hookline.delivery.default_timeout_seconds');
            $destination->max_attempts = (int) config('hookline.delivery.default_max_attempts');
            $destination->headers = null;
            $destination->save();

            $destinationSigningSecret = new DestinationSigningSecret();
            $destinationSigningSecret->destination()->associate($destination);
            $destinationSigningSecret->secret = WebhookSecret::mint();
            $destinationSigningSecret->save();

            return $destination;
        });
    }
}
