<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Tokens;

use Illuminate\Http\Response;
use Interfaces\Api\Contracts\Tokens\DestroyCurrentTokenControllerContract;

class DestroyCurrentTokenController implements DestroyCurrentTokenControllerContract
{
    public function __invoke(): Response
    {
        user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
