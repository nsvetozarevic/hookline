<?php

declare(strict_types=1);

namespace Interfaces\Api\Contracts;

use Illuminate\Http\Response;

interface DestroyCurrentTokenControllerContract
{
    public function __invoke(): Response;
}
