<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\EndpointEvents;

use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Interfaces\Api\Contracts\EndpointEvents\ShowEndpointEventControllerContract;
use Interfaces\Api\V1\Resources\ShowEndpointEventResource;

class ShowEndpointEventController implements ShowEndpointEventControllerContract
{
    public function __invoke(string $version, EndpointEvent $endpointEvent): JsonResponse
    {
        Gate::authorize('view', $endpointEvent->endpoint);

        $endpointEvent->load([
            'endpoint',
            'deliveries' => fn ($query) => $query->orderBy('id')->with('destination'),
        ]);

        return ShowEndpointEventResource::make($endpointEvent)->response();
    }
}
