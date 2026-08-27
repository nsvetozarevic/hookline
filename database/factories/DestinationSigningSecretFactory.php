<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Endpoint\Utility\SigningSecret;
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
            'secret' => SigningSecret::mint(),
            'expires_at' => null,
        ];
    }
}
