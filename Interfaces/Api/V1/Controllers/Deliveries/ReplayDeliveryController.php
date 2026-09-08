<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Deliveries;

use Domain\Delivery\Actions\ReplayDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Interfaces\Api\Contracts\Deliveries\ReplayDeliveryControllerContract;
use Interfaces\Api\V1\Requests\ReplayDeliveryRequest;

class ReplayDeliveryController implements ReplayDeliveryControllerContract
{
    public function __construct(private ReplayDelivery $replayDelivery)
    {
    }

    public function __invoke(string $version, Delivery $delivery, ReplayDeliveryRequest $request): JsonResponse
    {
        $this->replayDelivery->handle($delivery);

        return new JsonResponse(status: 202);
    }
}
