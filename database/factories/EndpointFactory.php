<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Endpoint>
 */
class EndpointFactory extends Factory
{
    protected $model = Endpoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'capture_token' => Str::lower(Str::random(32)),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Endpoint $endpoint): void {
            if ($endpoint->signingSecrets()->exists()) {
                return;
            }

            EndpointSigningSecret::factory()->for($endpoint)->create();
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
