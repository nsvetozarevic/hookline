<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\Endpoint\Utility\SigningSecret;
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
            'secret' => SigningSecret::mint(),
            'expires_at' => null,
        ];
    }
}
