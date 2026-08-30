<?php

declare(strict_types=1);

namespace Domain\Delivery\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case InFlight = 'in_flight';
    case Succeeded = 'succeeded';
    case Dead = 'dead';

    /** @return list<self> */
    public static function replayable(): array
    {
        return [self::Dead, self::Succeeded];
    }

    /** @return list<string> */
    public static function replayableValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::replayable());
    }

    public function isReplayable(): bool
    {
        return in_array($this, self::replayable(), true);
    }
}
