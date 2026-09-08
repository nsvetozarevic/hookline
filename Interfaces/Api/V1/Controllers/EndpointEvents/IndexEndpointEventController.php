<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\EndpointEvents;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Interfaces\Api\Contracts\EndpointEvents\IndexEndpointEventControllerContract;
use Interfaces\Api\V1\Resources\IndexEndpointEventResource;

class IndexEndpointEventController implements IndexEndpointEventControllerContract
{
    public function __invoke(string $version, Endpoint $endpoint): JsonResponse
    {
        Gate::authorize('view', $endpoint);

        return IndexEndpointEventResource::collection(
            $endpoint->endpointEvents()->latest()->paginate(25),
        )->response();
    }
}
