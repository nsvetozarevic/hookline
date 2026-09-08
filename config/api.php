<?php

declare(strict_types=1);

use Interfaces\Api\Contracts\DestroyCurrentTokenControllerContract;
use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\Contracts\ShowUserControllerContract;
use Interfaces\Api\Contracts\StoreTokenControllerContract;
use Interfaces\Api\V1\Controllers\DestroyCurrentTokenController;
use Interfaces\Api\V1\Controllers\PingController;
use Interfaces\Api\V1\Controllers\ShowUserController;
use Interfaces\Api\V1\Controllers\StoreTokenController;

return [
    'versions' => [
        1 => [
            PingControllerContract::class => PingController::class,
            StoreTokenControllerContract::class => StoreTokenController::class,
            DestroyCurrentTokenControllerContract::class => DestroyCurrentTokenController::class,
            ShowUserControllerContract::class => ShowUserController::class,
        ],
    ],
];
