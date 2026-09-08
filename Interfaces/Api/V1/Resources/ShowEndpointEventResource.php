<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EndpointEvent
 */
class ShowEndpointEventResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: string, deduplication_key: string, headers: array<string, string>, payload: string, created_at: string|null, deliveries: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'deduplication_key' => $this->deduplication_key,
            'headers' => $this->headers,
            'payload' => $this->payload,
            'created_at' => $this->created_at?->toJSON(),
            'deliveries' => IndexDeliveryResource::collection($this->deliveries),
        ];
    }
}
