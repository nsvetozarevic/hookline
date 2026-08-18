<?php

use Illuminate\Support\Facades\Route;
use Interfaces\Api\Controllers\PingController;

Route::get('/ping', PingController::class);
