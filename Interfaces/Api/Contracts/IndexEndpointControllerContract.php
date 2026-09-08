<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts;

use Illuminate\Http\JsonResponse;

interface IndexEndpointControllerContract
{
    public function __invoke(): JsonResponse;
}
