<?php

declare(strict_types=1);

use App\Routing\WebRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;
use Interfaces\Panel\Livewire\Endpoints\IndexEndpointComponent;

Route::post('/capture/{captureToken}', CaptureWebhookController::class)
    ->middleware('throttle:capture')
    ->name(WebRoute::Capture);

Route::middleware('auth')->group(function (): void {
    Route::get('/endpoints', IndexEndpointComponent::class)
        ->name(WebRoute::EndpointsIndex);
});
