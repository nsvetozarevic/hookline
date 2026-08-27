<?php

declare(strict_types=1);

namespace Domain\Endpoint\Utility;

class SigningSecret
{
    private const string PREFIX = 'whsec_';

    private const int BYTE_LENGTH = 32;

    public static function mint(): string
    {
        return sprintf('%s%s', self::PREFIX, base64_encode(random_bytes(self::BYTE_LENGTH)));
    }

    public static function decode(string $signingSecret): ?string
    {
        if (! str_starts_with($signingSecret, self::PREFIX)) {
            return null;
        }

        $encoded = substr($signingSecret, strlen(self::PREFIX));
        if ($encoded === '') {
            return null;
        }

        $secret = base64_decode($encoded, true);
        if ($secret === false || $secret === '') {
            return null;
        }

        return $secret;
    }
}
