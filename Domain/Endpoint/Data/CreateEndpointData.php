<?php

declare(strict_types=1);

namespace Domain\Endpoint\Data;

final readonly class CreateEndpointData
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?string $provider = null,
    ) {
    }
}
