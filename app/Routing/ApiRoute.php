<?php

declare(strict_types=1);

namespace App\Routing;

enum ApiRoute: string
{
    case Ping = 'api.ping';
}
