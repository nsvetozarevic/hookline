<?php

declare(strict_types=1);

namespace Domain\Endpoint\Actions;

use Domain\Endpoint\Data\CreateEndpointData;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\Endpoint\Utility\SigningSecret;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEndpoint
{
    public function handle(CreateEndpointData $createEndpointData): Endpoint
    {
        return DB::transaction(function () use ($createEndpointData): Endpoint {
            $endpoint = new Endpoint();

            $endpoint->user_id = $createEndpointData->userId;
            $endpoint->name = $createEndpointData->name;
            $endpoint->provider = $createEndpointData->provider;
            $endpoint->capture_token = Str::lower(Str::random(32));
            $endpoint->is_active = true;
            $endpoint->save();

            $endpointSigningSecret = new EndpointSigningSecret();
            $endpointSigningSecret->endpoint()->associate($endpoint);
            $endpointSigningSecret->secret = SigningSecret::mint();
            $endpointSigningSecret->save();

            return $endpoint;
        });
    }
}
