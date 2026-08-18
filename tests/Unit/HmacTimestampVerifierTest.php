<?php

namespace Tests\Unit;

use Domain\Endpoint\Utility\HmacTimestampVerifier;
use PHPUnit\Framework\TestCase;

class HmacTimestampVerifierTest extends TestCase
{
    private HmacTimestampVerifier $hmacTimestampVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hmacTimestampVerifier = new HmacTimestampVerifier();
    }

    public function test_valid_signature_passes(): void
    {
        $signingSecret = 'test-secret';
        $timestamp = '1700000000';
        $rawRequestBody = '{"ok":true}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawRequestBody, $signingSecret);

        $this->assertTrue(
            $this->hmacTimestampVerifier->verify(
                $signingSecret,
                $timestamp,
                $rawRequestBody,
                $signature,
                300,
                1700000000,
            ),
        );
    }

    public function test_wrong_signature_fails(): void
    {
        $this->assertFalse(
            $this->hmacTimestampVerifier->verify(
                'secret',
                '1700000000',
                '{}',
                'deadbeef',
                300,
                1700000000,
            ),
        );
    }

    public function test_stale_timestamp_fails(): void
    {
        $signingSecret = 'test-secret';
        $timestamp = '1700000000';
        $rawRequestBody = '{}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawRequestBody, $signingSecret);

        $this->assertFalse(
            $this->hmacTimestampVerifier->verify(
                $signingSecret,
                $timestamp,
                $rawRequestBody,
                $signature,
                300,
                1700000000 + 301,
            ),
        );
    }

    public function test_future_timestamp_fails(): void
    {
        $signingSecret = 'test-secret';
        $timestamp = '1700000301';
        $rawRequestBody = '{}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawRequestBody, $signingSecret);

        $this->assertFalse(
            $this->hmacTimestampVerifier->verify(
                $signingSecret,
                $timestamp,
                $rawRequestBody,
                $signature,
                300,
                1700000000,
            ),
        );
    }

    public function test_non_digit_timestamp_fails(): void
    {
        $this->assertFalse(
            $this->hmacTimestampVerifier->verify('secret', 'not-a-time', '{}', 'abc', 300, time()),
        );
    }
}
