<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Deliveries;

use Domain\Delivery\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Interfaces\Api\Contracts\Deliveries\ShowDeliveryControllerContract;
use Interfaces\Api\V1\Resources\ShowDeliveryResource;

class ShowDeliveryController implements ShowDeliveryControllerContract
{
    public function __invoke(string $version, Delivery $delivery): JsonResponse
    {
        Gate::authorize('view', $delivery->endpointEvent->endpoint);

        $delivery->load([
            'destination',
            'endpointEvent',
            'deliveryAttempts' => fn ($query) => $query->orderBy('attempt_number'),
        ]);

        return ShowDeliveryResource::make($delivery)->response();
    }
}
