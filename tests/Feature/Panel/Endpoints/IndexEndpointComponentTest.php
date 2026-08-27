<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Utility\SigningSecret;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interfaces\Panel\Livewire\Endpoints\IndexEndpointComponent;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexEndpointComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $this->get(route(WebRoute::IndexEndpoints))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_is_rendered_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route(WebRoute::IndexEndpoints))
            ->assertOk();
    }

    #[Test]
    public function it_lists_current_users_endpoints(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create(['name' => 'Stripe']);
        Endpoint::factory()->for($other)->create(['name' => 'GitHub']);

        $this->actingAs($user);

        Livewire::test(IndexEndpointComponent::class)
            ->assertSee('Stripe')
            ->assertSee(route(WebRoute::ShowEndpoints, $endpoint), false)
            ->assertDontSee('GitHub');
    }

    #[Test]
    public function create_endpoint_persists_a_new_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(IndexEndpointComponent::class)
            ->set('form.name', 'Stripe')
            ->set('form.provider', 'stripe')
            ->call('createEndpoint')
            ->assertHasNoErrors();

        $endpoint = Endpoint::query()->sole();

        $component->assertRedirect(route(WebRoute::ShowEndpoints, $endpoint));

        $this->assertSame($user->id, $endpoint->user_id);
        $this->assertSame('Stripe', $endpoint->name);
        $this->assertSame('stripe', $endpoint->provider);
        $this->assertTrue($endpoint->is_active);
        $this->assertSame(32, strlen($endpoint->capture_token));
        $this->assertSame(strtolower($endpoint->capture_token), $endpoint->capture_token);
        $decodedSigningSecret = SigningSecret::decode($endpoint->signing_secret);
        $this->assertNotNull($decodedSigningSecret);
        $this->assertSame(32, strlen($decodedSigningSecret));
    }

    /**
     * @param  array<string, string>  $invalidData
     */
    #[DataProvider('invalidData')]
    #[Test]
    public function create_endpoint_rejects_invalid_data(array $invalidData, string $fieldKey): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(IndexEndpointComponent::class)
            ->set('form.name', $invalidData['name'])
            ->set('form.provider', $invalidData['provider'])
            ->call('createEndpoint')
            ->assertHasErrors([$fieldKey]);

        $this->assertDatabaseCount('endpoints', 0);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidData(): array
    {
        return [
            'name - required' => [
                ['name' => '', 'provider' => 'stripe'],
                'form.name',
            ],
            'name - max:255' => [
                ['name' => str_repeat('a', 256), 'provider' => 'stripe'],
                'form.name',
            ],
            'provider - max:255' => [
                ['name' => 'Stripe', 'provider' => str_repeat('a', 256)],
                'form.provider',
            ],
        ];
    }
}
