<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Resources;

use Domain\Delivery\Models\DeliveryAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryAttempt
 */
class IndexDeliveryAttemptResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{attempt_number: int, result: string, response_status: int|null, response_body_snippet: string|null, duration_ms: int, error: string|null, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'attempt_number' => $this->attempt_number,
            'result' => $this->result->value,
            'response_status' => $this->response_status,
            'response_body_snippet' => $this->response_body_snippet,
            'duration_ms' => $this->duration_ms,
            'error' => $this->error,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
