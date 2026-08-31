<?php

declare(strict_types=1);

namespace Interfaces\Panel\Middleware;

use App\Routing\WebRoute;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectHome
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('/')) {
            return $next($request);
        }

        if ($request->user() !== null) {
            return redirect(config('fortify.home'));
        }

        return redirect()->route(WebRoute::ShowLogin);
    }
}
