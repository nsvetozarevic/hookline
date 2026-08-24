<?php

declare(strict_types=1);

namespace App\Providers;

use App\Routing\WebRoute;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Interfaces\Panel\Fortify\CreateNewUser;
use Interfaces\Panel\Livewire\Auth\Login;
use Interfaces\Panel\Livewire\Auth\Register;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::loginView(fn () => app(Login::class)());
        Fortify::registerView(fn () => app(Register::class)());

        RedirectIfAuthenticated::redirectUsing(fn () => route(WebRoute::IndexEndpoints));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
