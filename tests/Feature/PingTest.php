<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Routing\ApiRoute;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PingTest extends TestCase
{
    #[Test]
    public function ping_endpoint_returns_pong(): void
    {
        $response = $this->getJson(route(ApiRoute::Ping));

        $response->assertSuccessful()
            ->assertExactJson(['ping' => 'pong']);
    }
}
