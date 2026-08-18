<?php

namespace Domain\Endpoint\Utility;

class HmacTimestampVerifier
{
    public function verify(
        string $signingSecret,
        string $timestamp,
        string $rawRequestBody,
        string $signature,
        int $toleranceSeconds,
        ?int $currentUnixTimestamp = null,
    ): bool {
        if ($signingSecret === '' || $signature === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        $currentUnixTimestamp ??= time();
        $timestampAgeSeconds = abs($currentUnixTimestamp - (int) $timestamp);

        if ($timestampAgeSeconds > $toleranceSeconds) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$rawRequestBody, $signingSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
