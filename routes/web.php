<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;
use Interfaces\Panel\Livewire\Placeholder;

Route::get('/panel', Placeholder::class);

Route::post('/capture/{captureToken}', CaptureWebhookController::class)
    ->middleware('throttle:capture')
    ->name('capture');
