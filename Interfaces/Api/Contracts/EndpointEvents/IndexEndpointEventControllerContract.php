<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\EndpointEvents;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\JsonResponse;

interface IndexEndpointEventControllerContract
{
    public function __invoke(string $version, Endpoint $endpoint): JsonResponse;
}
