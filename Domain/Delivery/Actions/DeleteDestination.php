<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Models\Destination;

class DeleteDestination
{
    public function handle(Destination $destination): void
    {
        $destination->delete();
    }
}
