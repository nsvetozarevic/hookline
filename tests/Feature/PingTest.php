<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_ping_endpoint_returns_pong(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertSuccessful()
            ->assertExactJson(['ping' => 'pong']);
    }
}
