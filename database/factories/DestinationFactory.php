<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Delivery\Models\Destination;
use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Endpoint\Models\Endpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_id' => Endpoint::factory(),
            'url' => sprintf('https://example.test/webhooks/%s', fake()->uuid()),
            'is_active' => true,
            'timeout_seconds' => (int) config('hookline.delivery.default_timeout_seconds'),
            'max_attempts' => (int) config('hookline.delivery.default_max_attempts'),
            'headers' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Destination $destination): void {
            if ($destination->signingSecrets()->exists()) {
                return;
            }

            DestinationSigningSecret::factory()->for($destination)->create();
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
