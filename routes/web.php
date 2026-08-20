<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;

Route::view('/panel', 'panel.placeholder');

Route::post('/capture/{captureToken}', CaptureWebhookController::class)
    ->middleware('throttle:capture')
    ->name('capture');
