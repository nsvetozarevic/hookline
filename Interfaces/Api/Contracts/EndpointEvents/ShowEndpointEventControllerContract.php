<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\EndpointEvents;

use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Http\JsonResponse;

interface ShowEndpointEventControllerContract
{
    public function __invoke(string $version, EndpointEvent $endpointEvent): JsonResponse;
}
