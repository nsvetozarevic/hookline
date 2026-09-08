<?php

declare(strict_types=1);

use App\Routing\ApiRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Api\Contracts\DestroyCurrentTokenControllerContract;
use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\Contracts\StoreTokenControllerContract;
use Interfaces\Api\Middleware\BindApiVersion;

Route::prefix('v{version}')
    ->whereNumber('version')
    ->middleware(BindApiVersion::class)
    ->group(function (): void {
        Route::get('/ping', PingControllerContract::class)->name(ApiRoute::Ping);
        Route::post('/tokens', StoreTokenControllerContract::class)
            ->middleware('throttle:login')
            ->name(ApiRoute::StoreToken);
        Route::delete('/tokens/current', DestroyCurrentTokenControllerContract::class)
            ->middleware('auth:sanctum')
            ->name(ApiRoute::DestroyCurrentToken);
    });
