<?php

declare(strict_types=1);

namespace Domain\Delivery\Data;

final readonly class StoreDestinationData
{
    public function __construct(
        public int $endpointId,
        public string $url,
    ) {
    }
}
