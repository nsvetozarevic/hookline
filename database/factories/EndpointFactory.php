<?php

namespace Database\Factories;

use Domain\Endpoint\Models\Endpoint;
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
            'signing_secret' => Str::lower(Str::random(64)),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
