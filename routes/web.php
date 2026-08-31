<?php

declare(strict_types=1);

use App\Routing\WebRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;
use Interfaces\Panel\Livewire\Endpoints\IndexEndpointComponent;
use Interfaces\Panel\Livewire\Endpoints\ShowEndpointComponent;
use Interfaces\Panel\Livewire\Events\ShowEndpointEventComponent;

Route::post('/capture/{captureToken}', CaptureWebhookController::class)
    ->middleware('throttle:capture')
    ->name(WebRoute::Capture);

Route::prefix('user-panel')->middleware('auth')->group(function (): void {
    Route::get('/endpoints', IndexEndpointComponent::class)
        ->name(WebRoute::IndexEndpoints);
    Route::get('/endpoints/{endpoint}', ShowEndpointComponent::class)
        ->name(WebRoute::ShowEndpoints);
    Route::get('/events/{endpointEvent}', ShowEndpointEventComponent::class)
        ->name(WebRoute::ShowEvents);
});
