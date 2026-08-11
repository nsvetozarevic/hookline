<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['ping' => 'pong']);
    }
}
