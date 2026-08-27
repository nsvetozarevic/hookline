<?php

declare(strict_types=1);

namespace Domain\Delivery\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case InFlight = 'in_flight';
    case Succeeded = 'succeeded';
    case Dead = 'dead';
}
