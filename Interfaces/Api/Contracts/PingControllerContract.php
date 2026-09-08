<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts;

use Illuminate\Http\JsonResponse;

interface PingControllerContract
{
    public function __invoke(): JsonResponse;
}
