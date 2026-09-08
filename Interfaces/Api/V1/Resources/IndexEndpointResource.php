<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Endpoint
 */
class IndexEndpointResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: string, name: string, provider: string|null, is_active: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'provider' => $this->provider,
            'is_active' => $this->is_active,
        ];
    }
}
