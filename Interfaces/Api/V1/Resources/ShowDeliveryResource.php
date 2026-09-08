<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Delivery\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Delivery
 */
class ShowDeliveryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: string, status: string, attempts: int, last_status_code: int|null, destination_url: string, last_error: string|null, next_attempt_at: string|null, created_at: string|null, event_id: string, delivery_attempts: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'last_status_code' => $this->last_status_code,
            'destination_url' => $this->destination->url,
            'last_error' => $this->last_error,
            'next_attempt_at' => $this->next_attempt_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'event_id' => $this->endpointEvent->public_id,
            'delivery_attempts' => IndexDeliveryAttemptResource::collection($this->deliveryAttempts),
        ];
    }
}
