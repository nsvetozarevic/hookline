<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Delivery\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Delivery
 */
class IndexDeliveryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: string, status: string, attempts: int, last_status_code: int|null, destination_url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'last_status_code' => $this->last_status_code,
            'destination_url' => $this->destination->url,
        ];
    }
}
