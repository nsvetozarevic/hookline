<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function health_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertSuccessful();
    }
}
