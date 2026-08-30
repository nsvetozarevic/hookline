<?php

declare(strict_types=1);

namespace Domain\Delivery\Data;

final readonly class UpdateDestinationData
{
    public function __construct(
        public bool $isActive,
    ) {
    }
}
