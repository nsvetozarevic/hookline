<?php

declare(strict_types=1);

namespace Domain\Delivery\Utility;

use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Enums\DeliveryOutcome;

final class ClassifyDeliveryAttempt
{
    public static function fromHttpStatus(int $responseStatus): DeliveryAttemptResult
    {
        if ($responseStatus >= 200 && $responseStatus <= 299) {
            return DeliveryAttemptResult::Succeeded;
        }

        if ($responseStatus >= 500 || $responseStatus === 429 || $responseStatus === 408) {
            return DeliveryAttemptResult::Retryable;
        }

        return DeliveryAttemptResult::Failed;
    }

    public static function deliveryOutcome(DeliveryAttemptResult $attemptResult): DeliveryOutcome
    {
        return match ($attemptResult) {
            DeliveryAttemptResult::Succeeded => DeliveryOutcome::Succeeded,
            DeliveryAttemptResult::Retryable, DeliveryAttemptResult::ConnectionError => DeliveryOutcome::Retryable,
            DeliveryAttemptResult::Failed, DeliveryAttemptResult::Blocked => DeliveryOutcome::Failed,
        };
    }
}
