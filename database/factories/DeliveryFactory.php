<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_event_id' => EndpointEvent::factory(),
            'destination_id' => Destination::factory(),
            'status' => DeliveryStatus::Pending,
            'attempts' => 0,
            'next_attempt_at' => now(),
            'last_status_code' => null,
            'last_error' => null,
            'locked_at' => null,
        ];
    }
}
