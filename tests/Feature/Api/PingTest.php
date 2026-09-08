<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PingTest extends TestCase
{
    #[Test]
    public function v1_ping_returns_ok(): void
    {
        $this->getJson(route(ApiRoute::Ping, ['version' => 1]))
            ->assertOk()
            ->assertExactJson(['ok' => true]);
    }

    #[Test]
    public function unknown_api_version_returns_not_found(): void
    {
        $this->getJson('/api/v99/ping')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested API version does not exist.');
    }
}
