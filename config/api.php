<?php

declare(strict_types=1);

use Interfaces\Api\Contracts\Deliveries\ReplayDeliveryControllerContract;
use Interfaces\Api\Contracts\Deliveries\ShowDeliveryControllerContract;
use Interfaces\Api\Contracts\EndpointEvents\IndexEndpointEventControllerContract;
use Interfaces\Api\Contracts\EndpointEvents\ShowEndpointEventControllerContract;
use Interfaces\Api\Contracts\Endpoints\IndexEndpointControllerContract;
use Interfaces\Api\Contracts\Endpoints\ShowEndpointControllerContract;
use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\Contracts\Tokens\DestroyCurrentTokenControllerContract;
use Interfaces\Api\Contracts\Tokens\StoreTokenControllerContract;
use Interfaces\Api\Contracts\Users\ShowUserControllerContract;
use Interfaces\Api\V1\Controllers\Deliveries\ReplayDeliveryController;
use Interfaces\Api\V1\Controllers\Deliveries\ShowDeliveryController;
use Interfaces\Api\V1\Controllers\EndpointEvents\IndexEndpointEventController;
use Interfaces\Api\V1\Controllers\EndpointEvents\ShowEndpointEventController;
use Interfaces\Api\V1\Controllers\Endpoints\IndexEndpointController;
use Interfaces\Api\V1\Controllers\Endpoints\ShowEndpointController;
use Interfaces\Api\V1\Controllers\PingController;
use Interfaces\Api\V1\Controllers\Tokens\DestroyCurrentTokenController;
use Interfaces\Api\V1\Controllers\Tokens\StoreTokenController;
use Interfaces\Api\V1\Controllers\Users\ShowUserController;

return [
    'versions' => [
        1 => [
            PingControllerContract::class => PingController::class,

            StoreTokenControllerContract::class => StoreTokenController::class,
            DestroyCurrentTokenControllerContract::class => DestroyCurrentTokenController::class,

            ShowUserControllerContract::class => ShowUserController::class,

            IndexEndpointControllerContract::class => IndexEndpointController::class,
            ShowEndpointControllerContract::class => ShowEndpointController::class,

            IndexEndpointEventControllerContract::class => IndexEndpointEventController::class,
            ShowEndpointEventControllerContract::class => ShowEndpointEventController::class,

            ShowDeliveryControllerContract::class => ShowDeliveryController::class,
            ReplayDeliveryControllerContract::class => ReplayDeliveryController::class,
        ],
    ],
];
