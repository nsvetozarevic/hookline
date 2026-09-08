<?php

declare(strict_types=1);

use App\Routing\ApiRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Api\Contracts\Deliveries\ShowDeliveryControllerContract;
use Interfaces\Api\Contracts\EndpointEvents\IndexEndpointEventControllerContract;
use Interfaces\Api\Contracts\EndpointEvents\ShowEndpointEventControllerContract;
use Interfaces\Api\Contracts\Endpoints\IndexEndpointControllerContract;
use Interfaces\Api\Contracts\Endpoints\ShowEndpointControllerContract;
use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\Contracts\Tokens\DestroyCurrentTokenControllerContract;
use Interfaces\Api\Contracts\Tokens\StoreTokenControllerContract;
use Interfaces\Api\Contracts\Users\ShowUserControllerContract;
use Interfaces\Api\Middleware\BindApiVersion;

Route::prefix('v{version}')
    ->whereNumber('version')
    ->middleware(BindApiVersion::class)
    ->group(function (): void {
        Route::get('/ping', PingControllerContract::class)->name(ApiRoute::Ping);
        Route::post('/tokens', StoreTokenControllerContract::class)
            ->middleware('throttle:login')
            ->name(ApiRoute::StoreToken);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::delete('/tokens/current', DestroyCurrentTokenControllerContract::class)
                ->name(ApiRoute::DestroyCurrentToken);

            Route::get('/user', ShowUserControllerContract::class)
                ->name(ApiRoute::ShowUser);

            Route::get('/endpoints', IndexEndpointControllerContract::class)
                ->name(ApiRoute::IndexEndpoints);
            Route::get('/endpoints/{endpoint}', ShowEndpointControllerContract::class)
                ->name(ApiRoute::ShowEndpoints);

            Route::get('/endpoints/{endpoint}/events', IndexEndpointEventControllerContract::class)
                ->name(ApiRoute::IndexEndpointEvents);
            Route::get('/events/{endpointEvent}', ShowEndpointEventControllerContract::class)
                ->name(ApiRoute::ShowEvents);
                
            Route::get('/deliveries/{delivery}', ShowDeliveryControllerContract::class)
                ->name(ApiRoute::ShowDeliveries);
        });
    });
