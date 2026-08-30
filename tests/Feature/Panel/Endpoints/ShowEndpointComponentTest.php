<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Endpoints;

use App\Routing\WebRoute;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            ->assertSee($endpoint->currentSigningSecret->secret)
            ->assertSee(route(WebRoute::Capture, $endpoint->capture_token), false);
    }

    #[Test]
    public function rotating_the_signing_secret_keeps_the_previous_row(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $originalSigningSecret = $endpoint->currentSigningSecret;

        $this->actingAs($user);

        $component = Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('rotateSigningSecret')
            ->assertOk();

        $endpoint->unsetRelation('currentSigningSecret');
        $currentSigningSecret = $endpoint->currentSigningSecret;
        $originalSigningSecret = $originalSigningSecret->fresh();

        $this->assertNotSame($originalSigningSecret->secret, $currentSigningSecret->secret);
        $this->assertNull($currentSigningSecret->expires_at);
        $this->assertEquals(now()->addHours(48)->startOfSecond(), $originalSigningSecret->expires_at);
        $this->assertDatabaseCount('endpoint_signing_secrets', 2);

        $component
            ->assertSee($currentSigningSecret->secret)
            ->assertSee($originalSigningSecret->secret)
            ->assertSee($originalSigningSecret->expires_at->toDateTimeString());
    }

    #[Test]
    public function rotating_twice_keeps_every_unexpired_secret(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('rotateSigningSecret')
            ->call('rotateSigningSecret')
            ->assertOk();

        $this->assertDatabaseCount('endpoint_signing_secrets', 3);
        $this->assertSame(1, $endpoint->signingSecrets()->whereNull('expires_at')->count());
        $this->assertSame(2, $endpoint->signingSecrets()->whereNotNull('expires_at')->count());
    }

    #[Test]
    public function another_user_cannot_rotate_an_endpoint(): void
    {
        $owner = User::factory()->create();
        $secondUser = User::factory()->create();
        $endpoint = Endpoint::factory()->for($owner)->create();

        $this->actingAs($secondUser);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->assertForbidden();
    }

    #[Test]
    public function store_destination_adds_a_row_and_shows_it_on_the_page(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->set('form.url', 'https://example.com/webhooks')
            ->call('storeDestination')
            ->assertHasNoErrors()
            ->assertSee('https://example.com/webhooks');

        $destination = Destination::query()->where('endpoint_id', $endpoint->id)->sole();
        $this->assertSame('https://example.com/webhooks', $destination->url);
        $this->assertTrue($destination->is_active);
        $this->assertSame(1, $destination->signingSecrets()->count());
    }

    #[Test]
    public function store_destination_rejects_a_non_public_url(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->set('form.url', 'https://169.254.169.254/latest/meta-data')
            ->call('storeDestination')
            ->assertHasErrors(['form.url']);

        $this->assertDatabaseCount('destinations', 0);
    }

    #[Test]
    public function update_destination_toggles_active_state(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.com/webhooks',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('updateDestination', $destination->id)
            ->assertSee('Inactive')
            ->assertSee('Activate')
            ->call('updateDestination', $destination->id)
            ->assertSee('Active')
            ->assertSee('Deactivate');

        $this->assertTrue($destination->fresh()->is_active);
    }

    #[Test]
    public function update_destination_rejects_a_destination_on_another_endpoint(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $otherEndpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($otherEndpoint)->create();

        $this->actingAs($user);
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('updateDestination', $destination->id);
    }

    #[Test]
    public function delete_destination_removes_the_row(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.com/webhooks',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('deleteDestination', $destination->id)
            ->assertSee('No destinations yet');

        $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
    }

    #[Test]
    public function delete_destination_rejects_a_destination_on_another_endpoint(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $otherEndpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($otherEndpoint)->create();

        $this->actingAs($user);
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ShowEndpointComponent::class, ['endpoint' => $endpoint])
            ->call('deleteDestination', $destination->id);
    }
}
