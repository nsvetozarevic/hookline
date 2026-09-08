<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Interfaces\Api\Contracts\PingControllerContract;

class PingController implements PingControllerContract
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'ok' => true,
        ]);
    }
}
