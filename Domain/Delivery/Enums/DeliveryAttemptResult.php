<?php

declare(strict_types=1);

namespace Domain\Delivery\Enums;

enum DeliveryAttemptResult: string
{
    case Blocked = 'blocked';
    case ConnectionError = 'connection_error';
    case Succeeded = 'succeeded';
    case Retryable = 'retryable';
    case Failed = 'failed';
}
