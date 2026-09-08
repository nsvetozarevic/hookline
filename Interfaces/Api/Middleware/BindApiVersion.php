<?php

declare(strict_types=1);

namespace Interfaces\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Interfaces\Api\Exceptions\ApiVersionException;
use Symfony\Component\HttpFoundation\Response;

class BindApiVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<int, array<class-string, class-string>> $versions */
        $versions = config('api.versions');

        $requestedVersion = $request->route('version');

        if (! is_numeric($requestedVersion) || ! array_key_exists((int) $requestedVersion, $versions)) {
            throw ApiVersionException::invalidVersion();
        }

        foreach ($versions as $version => $classes) {
            foreach ($classes as $contract => $class) {
                app()->bind($contract, $class);
            }

            if ($version === (int) $requestedVersion) {
                break;
            }
        }

        return $next($request);
    }
}
