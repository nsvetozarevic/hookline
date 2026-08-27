<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interfaces\Panel\Livewire\Endpoints\ShowEndpointComponent;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowEndpointComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $endpoint = Endpoint::factory()->create();

        $this->get(route(WebRoute::ShowEndpoints, $endpoint))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_is_rendered_correctly(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create(['name' => 'Stripe']);

        $this->actingAs($user)
            ->get(route(WebRoute::ShowEndpoints, $endpoint))
            ->assertOk()
            ->assertSee('Stripe', false);
    }

    #[Test]
    public function it_shows_capture_credentials(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create([
            'name' => 'Stripe',
            'provider' => 'stripe',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->assertOk()
            ->assertSee('Stripe')
            ->assertSee('stripe')
            ->assertSee($endpoint->capture_token)
            ->assertSee($endpoint->signing_secret)
            ->assertSee(route(WebRoute::Capture, $endpoint->capture_token), false);
    }
}
