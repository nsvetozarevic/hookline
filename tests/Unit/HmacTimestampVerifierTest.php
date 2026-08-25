<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Endpoint\Utility\HmacTimestampVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HmacTimestampVerifierTest extends TestCase
{
    private HmacTimestampVerifier $hmacTimestampVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hmacTimestampVerifier = new HmacTimestampVerifier();
    }

    #[Test]
    public function valid_signature_passes(): void
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

    #[Test]
    public function wrong_signature_fails(): void
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

    #[Test]
    public function stale_timestamp_fails(): void
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

    #[Test]
    public function future_timestamp_fails(): void
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

    #[Test]
    public function empty_signing_secret_fails(): void
    {
        $timestamp = '1700000000';
        $rawRequestBody = '{}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawRequestBody, 'secret');

        $this->assertFalse(
            $this->hmacTimestampVerifier->verify(
                '',
                $timestamp,
                $rawRequestBody,
                $signature,
                300,
                1700000000,
            ),
        );
    }

    #[Test]
    public function empty_signature_fails(): void
    {
        $this->assertFalse(
            $this->hmacTimestampVerifier->verify(
                'secret',
                '1700000000',
                '{}',
                '',
                300,
                1700000000,
            ),
        );
    }

    #[Test]
    public function non_digit_timestamp_fails(): void
    {
        $this->assertFalse(
            $this->hmacTimestampVerifier->verify('secret', 'not-a-time', '{}', 'abc', 300, time()),
        );
    }
}
