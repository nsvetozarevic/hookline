<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DestinationSigningSecret>
 */
class DestinationSigningSecretFactory extends Factory
{
    protected $model = DestinationSigningSecret::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'secret' => WebhookSecret::mint(),
            'expires_at' => null,
        ];
    }
}
