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
}
