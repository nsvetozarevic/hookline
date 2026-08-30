<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\DeliveryAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAttempt>
 */
class DeliveryAttemptFactory extends Factory
{
    protected $model = DeliveryAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'attempt_number' => 1,
            'result' => DeliveryAttemptResult::Succeeded,
            'request_headers' => [],
            'response_status' => null,
            'response_body_snippet' => null,
            'duration_ms' => 0,
            'error' => null,
        ];
    }
}
