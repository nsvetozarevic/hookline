<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\Deliveries;

use Domain\Delivery\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Interfaces\Api\V1\Requests\ReplayDeliveryRequest;

interface ReplayDeliveryControllerContract
{
    public function __invoke(string $version, Delivery $delivery, ReplayDeliveryRequest $request): JsonResponse;
}
