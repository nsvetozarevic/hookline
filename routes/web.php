<?php

declare(strict_types=1);

use App\Routing\WebRoute;
use Illuminate\Support\Facades\Route;
use Interfaces\Inbound\Controllers\CaptureWebhookController;
use Interfaces\Panel\Livewire\Endpoints\IndexEndpointComponent;
use Interfaces\Panel\Livewire\Endpoints\ShowEndpointComponent;
use Interfaces\Panel\Livewire\Events\ShowEndpointEventComponent;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

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

Route::middleware(config('fortify.middleware'))->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->middleware(['guest:'.config('fortify.guard')])
        ->name(WebRoute::ShowLogin);

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['guest:'.config('fortify.guard'), 'throttle:login'])
        ->name(WebRoute::Login);

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])
        ->name(WebRoute::Logout);

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->middleware(['guest:'.config('fortify.guard')])
        ->name(WebRoute::ShowRegister);

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware(['guest:'.config('fortify.guard'), 'throttle:register'])
        ->name(WebRoute::Register);
});
