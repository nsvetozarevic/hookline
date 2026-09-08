<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Interfaces\Api\Contracts\Users\ShowUserControllerContract;
use Interfaces\Api\V1\Resources\UserResource;

class ShowUserController implements ShowUserControllerContract
{
    public function __invoke(): JsonResponse
    {
        return UserResource::make(user())->response();
    }
}
