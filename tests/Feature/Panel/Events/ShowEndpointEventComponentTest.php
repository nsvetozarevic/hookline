<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Events;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interfaces\Panel\Livewire\Events\ShowEndpointEventComponent;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowEndpointEventComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $endpointEvent = EndpointEvent::factory()->create();

        $this->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_is_rendered_correctly(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create(['name' => 'Stripe']);
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_visible',
        ]);

        $this->actingAs($user)
            ->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertOk()
            ->assertSee('evt_visible', false)
            ->assertSee('Stripe', false);
    }

    #[Test]
    public function it_pretty_prints_json_payload_and_shows_headers(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'headers' => [
                'content-type' => 'application/json',
                'user-agent' => 'stripe-webhooks/1.0',
            ],
            'payload' => '{"ok":true}',
        ]);

        $this->actingAs($user);

        $prettyPrintedPayload = json_encode(
            json_decode('{"ok":true}'),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('content-type')
            ->assertSee('application/json')
            ->assertSee('user-agent')
            ->assertSee('stripe-webhooks/1.0')
            ->assertSee($prettyPrintedPayload)
            ->assertDontSee('Truncated,');
    }

    #[Test]
    public function it_shows_a_non_json_payload_raw(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'payload' => 'not-json <raw>',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('not-json <raw>');
    }

    #[Test]
    public function it_caps_the_displayed_payload(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'payload' => str_repeat('a', 64 * 1024).'TAIL',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertDontSee('TAIL')
            ->assertSee('Truncated, 65 KB total.');
    }
}
