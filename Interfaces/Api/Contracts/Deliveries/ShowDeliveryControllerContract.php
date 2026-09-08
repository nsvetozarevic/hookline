<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\Deliveries;

use Domain\Delivery\Models\Delivery;
use Illuminate\Http\JsonResponse;

interface ShowDeliveryControllerContract
{
    public function __invoke(string $version, Delivery $delivery): JsonResponse;
}
