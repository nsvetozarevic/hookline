<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Endpoints;

use Illuminate\Http\JsonResponse;
use Interfaces\Api\Contracts\Endpoints\IndexEndpointControllerContract;
use Interfaces\Api\V1\Resources\IndexEndpointResource;

class IndexEndpointController implements IndexEndpointControllerContract
{
    public function __invoke(): JsonResponse
    {
        return IndexEndpointResource::collection(
            user()->endpoints()->latest()->get(),
        )->response();
    }
}
