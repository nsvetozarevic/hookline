<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Interfaces\Api\Contracts\IndexEndpointControllerContract;
use Interfaces\Api\V1\Resources\EndpointResource;

class IndexEndpointController implements IndexEndpointControllerContract
{
    public function __invoke(): JsonResponse
    {
        return EndpointResource::collection(
            user()->endpoints()->latest()->get(),
        )->response();
    }
}
