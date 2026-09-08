<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts;

use Illuminate\Http\JsonResponse;
use Interfaces\Api\V1\Requests\StoreTokenRequest;

interface StoreTokenControllerContract
{
    public function __invoke(StoreTokenRequest $request): JsonResponse;
}
