<?php

declare(strict_types=1);

namespace Interfaces\Api\Exceptions;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiVersionException extends HttpException
{
    public static function invalidVersion(): self
    {
        return new self(
            SymfonyResponse::HTTP_NOT_FOUND,
            'The requested API version does not exist.',
        );
    }
}
