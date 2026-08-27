<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            return sprintf('Database\\Factories\\%sFactory', class_basename($modelName));
        });

        RateLimiter::for('capture', function (Request $request) {
            return Limit::perMinute((int) config('hookline.capture.rate_limit_per_minute'))
                ->by((string) $request->route('captureToken'));
        });
    }
}
