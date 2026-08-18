<?php

namespace Database\Factories;

use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EndpointEvent>
 */
class EndpointEventFactory extends Factory
{
    protected $model = EndpointEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_id' => Endpoint::factory(),
            'deduplication_key' => fake()->unique()->uuid(),
            'headers' => ['content-type' => 'application/json'],
            'payload' => '{"ok":true}',
            'received_at' => now(),
        ];
    }
}
