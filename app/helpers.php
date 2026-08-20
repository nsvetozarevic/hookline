<?php

declare(strict_types=1);

use Domain\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

function user(): User
{
    $user = Auth::user();

    if (! $user instanceof User) {
        throw new AuthenticationException();
    }

    return $user;
}
