<?php

use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;

Route::post('/capture/{captureToken}', CaptureWebhookController::class)
    ->name('capture');
