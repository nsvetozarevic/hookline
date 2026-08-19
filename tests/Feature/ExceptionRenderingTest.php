<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExceptionRenderingTest extends TestCase
{
    public function test_unknown_capture_token_returns_json_not_found(): void
    {
        $response = $this->post('/capture/missing-token');

        $response->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_missing_page_returns_html_not_found(): void
    {
        $response = $this->get('/this-page-does-not-exist', [
            'Accept' => 'text/html, application/xhtml+xml',
        ]);

        $response->assertNotFound();

        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
    }

    public function test_missing_page_returns_json_when_the_client_expects_json(): void
    {
        $this->getJson('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_api_route_returns_json_not_found(): void
    {
        $this->get('/api/this-does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
