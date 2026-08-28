<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EndpointSigningSecret>
 */
class EndpointSigningSecretFactory extends Factory
{
    protected $model = EndpointSigningSecret::class;

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
