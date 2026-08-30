<?php

declare(strict_types=1);

namespace Domain\Delivery\Data;

use Domain\Delivery\Enums\DeliveryAttemptResult;

final readonly class DeliverySendResult
{
    /**
     * @param  array<string, string>  $requestHeaders
     */
    public function __construct(
        public array $requestHeaders,
        public int $durationMs,
        public DeliveryAttemptResult $attemptResult,
        public ?int $responseStatus,
        public ?string $responseBodySnippet,
        public ?string $error,
    ) {
    }
}
