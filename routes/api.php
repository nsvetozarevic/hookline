<?php

declare(strict_types=1);

use App\Routing\ApiRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Api\Contracts\PingControllerContract;
use Interfaces\Api\Middleware\BindApiVersion;

Route::prefix('v{version}')
    ->whereNumber('version')
    ->middleware(BindApiVersion::class)
    ->group(function (): void {
        Route::get('/ping', PingControllerContract::class)->name(ApiRoute::Ping);
    });
