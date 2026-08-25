<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndpointPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_from_endpoint_show(): void
    {
        $endpoint = Endpoint::factory()->create();

        $this->get(route(WebRoute::ShowEndpoints, $endpoint))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_redirects_guests_from_event_show(): void
    {
        $endpointEvent = EndpointEvent::factory()->create();

        $this->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_allows_the_owner_to_view_an_endpoint(): void
    {
        $owner = User::factory()->create();
        $endpoint = Endpoint::factory()->for($owner)->create(['name' => 'Stripe']);

        $this->actingAs($owner)
            ->get(route(WebRoute::ShowEndpoints, $endpoint))
            ->assertOk()
            ->assertSee('Stripe', false);
    }

    #[Test]
    public function it_forbids_another_user_from_viewing_an_endpoint(): void
    {
        $owner = User::factory()->create();
        $secondUser = User::factory()->create();
        $endpoint = Endpoint::factory()->for($owner)->create();

        $this->actingAs($secondUser)
            ->get(route(WebRoute::ShowEndpoints, $endpoint))
            ->assertForbidden();
    }

    #[Test]
    public function it_allows_the_owner_to_view_an_event(): void
    {
        $owner = User::factory()->create();
        $endpoint = Endpoint::factory()->for($owner)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_owned',
        ]);

        $this->actingAs($owner)
            ->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertOk()
            ->assertSee('evt_owned', false);
    }

    #[Test]
    public function it_forbids_another_user_from_viewing_an_event(): void
    {
        $owner = User::factory()->create();
        $secondUser = User::factory()->create();
        $endpoint = Endpoint::factory()->for($owner)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();

        $this->actingAs($secondUser)
            ->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertForbidden();
    }
}
