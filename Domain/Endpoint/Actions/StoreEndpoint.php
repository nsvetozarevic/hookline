<?php

declare(strict_types=1);

namespace Domain\Endpoint\Actions;

use Domain\Endpoint\Data\StoreEndpointData;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreEndpoint
{
    public function handle(StoreEndpointData $storeEndpointData): Endpoint
    {
        return DB::transaction(function () use ($storeEndpointData): Endpoint {
            $endpoint = new Endpoint();

            $endpoint->user_id = $storeEndpointData->userId;
            $endpoint->name = $storeEndpointData->name;
            $endpoint->provider = $storeEndpointData->provider;
            $endpoint->capture_token = Str::lower(Str::random(32));
            $endpoint->is_active = true;
            $endpoint->save();

            $endpointSigningSecret = new EndpointSigningSecret();
            $endpointSigningSecret->endpoint()->associate($endpoint);
            $endpointSigningSecret->secret = WebhookSecret::mint();
            $endpointSigningSecret->save();

            return $endpoint;
        });
    }
}
