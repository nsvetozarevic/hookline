<?php

declare(strict_types=1);

namespace Domain\Endpoint\Actions;

use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Support\Facades\DB;
use LogicException;

class RotateEndpointSigningSecret
{
    public function handle(Endpoint $endpoint): Endpoint
    {
        return DB::transaction(function () use ($endpoint): Endpoint {
            $currentSigningSecret = $endpoint->currentSigningSecret()->first();

            if ($currentSigningSecret === null) {
                throw new LogicException('Endpoint has no current signing secret.');
            }

            $currentSigningSecret->expires_at = now()
                ->addHours((int) config('hookline.webhooks.secret_rotation_grace_hours'))
                ->startOfSecond();
            $currentSigningSecret->save();

            $endpointSigningSecret = new EndpointSigningSecret();
            $endpointSigningSecret->endpoint()->associate($endpoint);
            $endpointSigningSecret->secret = WebhookSecret::mint();
            $endpointSigningSecret->save();

            $endpoint->refresh();

            return $endpoint;
        });
    }
}
