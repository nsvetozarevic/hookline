<?php

declare(strict_types=1);

use App\Routing\ApiRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Api\Controllers\PingController;

Route::get('/ping', PingController::class)->name(ApiRoute::Ping);
