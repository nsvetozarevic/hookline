<?php

declare(strict_types=1);

namespace App\Routing;

enum ApiRoute: string
{
    case Ping = 'api.ping';
    case StoreToken = 'api.tokens.store';
    case DestroyCurrentToken = 'api.tokens.current.destroy';
    case ShowUser = 'api.user.show';
    case IndexEndpoints = 'api.endpoints.index';
}
