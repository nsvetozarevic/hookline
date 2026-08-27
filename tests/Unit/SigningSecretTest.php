<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Endpoint\Utility\SigningSecret;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SigningSecretTest extends TestCase
{
    #[Test]
    public function mint_round_trips_through_decode(): void
    {
        $printable = SigningSecret::mint();
        $secret = SigningSecret::decode($printable);

        $this->assertNotNull($secret);
        $this->assertSame(32, strlen($secret));
    }

    #[Test]
    public function decode_rejects_a_string_that_is_not_whsec_base64(): void
    {
        $this->assertNull(SigningSecret::decode('not-whsec'));
        $this->assertNull(SigningSecret::decode('whsec_'));
        $this->assertNull(SigningSecret::decode('whsec_!!!'));
    }
}
