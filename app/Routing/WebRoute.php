<?php

declare(strict_types=1);

namespace App\Routing;

enum WebRoute: string
{
    case Capture = 'capture';
    case EndpointsIndex = 'endpoints.index';
    case ShowLogin = 'login';
    case Login = 'login.store';
    case Logout = 'logout';
    case ShowRegister = 'register';
    case Register = 'register.store';
}
