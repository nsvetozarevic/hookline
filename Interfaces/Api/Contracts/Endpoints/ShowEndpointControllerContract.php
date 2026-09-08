<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\Endpoints;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\JsonResponse;

interface ShowEndpointControllerContract
{
    public function __invoke(string $version, Endpoint $endpoint): JsonResponse;
}
