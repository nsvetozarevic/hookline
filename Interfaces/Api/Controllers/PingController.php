<?php

declare(strict_types=1);

namespace Interfaces\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['ping' => 'pong']);
    }
}
