<?php

declare(strict_types=1);

namespace Domain\Delivery\Enums;

enum DeliveryOutcome
{
    case Succeeded;
    case Retryable;
    case Failed;
}
