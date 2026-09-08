<?php

declare(strict_types=1);

use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\V1\Controllers\PingController;

return [
    'versions' => [
        1 => [
            PingControllerContract::class => PingController::class,
        ],
    ],
];
