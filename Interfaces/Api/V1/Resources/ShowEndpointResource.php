<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Endpoint
 */
class ShowEndpointResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: string, name: string, provider: string|null, capture_token: string, signing_secret: string|null, is_active: bool, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'provider' => $this->provider,
            'capture_token' => $this->capture_token,
            'signing_secret' => $this->currentSigningSecret?->secret,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
