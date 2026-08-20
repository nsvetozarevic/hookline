<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PingTest extends TestCase
{
    #[Test]
    public function ping_endpoint_returns_pong(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertSuccessful()
            ->assertExactJson(['ping' => 'pong']);
    }
}
