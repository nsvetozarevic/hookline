<?php

declare(strict_types=1);

namespace Domain\Endpoint\Policies;

use Domain\Endpoint\Models\Endpoint;
use Domain\User\Models\User;

class EndpointPolicy
{
    public function view(User $user, Endpoint $endpoint): bool
    {
        return $user->id === $endpoint->user_id;
    }
}
