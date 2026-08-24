<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interfaces\Panel\Livewire\Endpoints\ShowEndpointComponent;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_an_empty_state_when_the_endpoint_has_no_events(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->assertSee('No events yet — try the curl above.');
    }

    #[Test]
    public function it_lists_event_details(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_visible',
            'headers' => ['content-type' => 'application/json'],
            'payload' => '{"ok":true}',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->assertDontSee('No events yet — try the curl above.')
            ->assertSee('#'.$endpointEvent->id)
            ->assertSee(route(WebRoute::ShowEvents, $endpointEvent), false)
            ->assertSee('evt_visible')
            ->assertSee('application/json')
            ->assertSee($endpointEvent->created_at->toDateTimeString())
            ->assertSee(strlen('{"ok":true}').' B');
    }

    #[Test]
    public function it_paginates_the_endpoints_events(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $secondEndpoint = Endpoint::factory()->create();

        EndpointEvent::factory()
            ->for($endpoint)
            ->count(30)
            ->sequence(fn (Sequence $sequence): array => [
                'deduplication_key' => sprintf('evt_%02d', $sequence->index + 1),
                'created_at' => now()->subSeconds(30 - $sequence->index),
                'updated_at' => now()->subSeconds(30 - $sequence->index),
            ])
            ->create();

        EndpointEvent::factory()->for($secondEndpoint)->create([
            'deduplication_key' => 'evt_other',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->assertSee('evt_30')
            ->assertDontSee('evt_05')
            ->assertDontSee('evt_other');

        $this->assertSame(25, substr_count($component->html(), 'wire:key="endpoint-event-'));

        $component->call('nextPage')
            ->assertSee('evt_05')
            ->assertDontSee('evt_30')
            ->assertDontSee('evt_other');

        $this->assertSame(5, substr_count($component->html(), 'wire:key="endpoint-event-'));
    }
}
