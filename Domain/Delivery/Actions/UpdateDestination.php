<?php

declare(strict_types=1);

namespace Domain\Delivery\Actions;

use Domain\Delivery\Data\UpdateDestinationData;
use Domain\Delivery\Models\Destination;

class UpdateDestination
{
    public function handle(Destination $destination, UpdateDestinationData $updateDestinationData): Destination
    {
        $destination->is_active = $updateDestinationData->isActive;
        $destination->save();

        return $destination;
    }
}
