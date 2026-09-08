<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Endpoints;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Interfaces\Api\Contracts\Endpoints\ShowEndpointControllerContract;
use Interfaces\Api\V1\Resources\ShowEndpointResource;

class ShowEndpointController implements ShowEndpointControllerContract
{
    public function __invoke(string $version, Endpoint $endpoint): JsonResponse
    {
        Gate::authorize('view', $endpoint);

        $endpoint->load('currentSigningSecret');

        return ShowEndpointResource::make($endpoint)->response();
    }
}
