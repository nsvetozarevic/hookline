<?php

declare(strict_types=1);

use App\Routing\WebRoute;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use Interfaces\Console\Commands\DispatchDueDeliveriesCommand;
use Interfaces\Console\Commands\ReleaseStuckDeliveriesCommand;
use Interfaces\Panel\Middleware\RedirectHome;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        DispatchDueDeliveriesCommand::class,
        ReleaseStuckDeliveriesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route(WebRoute::ShowLogin));
        $middleware->redirectUsersTo(fn () => route(WebRoute::IndexEndpoints));

        $middleware->append(RedirectHome::class);

        $middleware->validateCsrfTokens(except: [
            'capture/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('capture/*')
                || $request->expectsJson(),
        );
    })->create();
