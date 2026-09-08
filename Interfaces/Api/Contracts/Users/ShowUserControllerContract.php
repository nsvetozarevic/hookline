<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts\Users;

use Illuminate\Http\JsonResponse;

interface ShowUserControllerContract
{
    public function __invoke(): JsonResponse;
}
