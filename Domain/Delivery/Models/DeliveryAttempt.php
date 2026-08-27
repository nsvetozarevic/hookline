<?php

declare(strict_types=1);

namespace Domain\Delivery\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, string> $request_headers
 */
#[Fillable([
    'delivery_id',
    'attempt_number',
    'request_headers',
    'response_status',
    'response_body_snippet',
    'duration_ms',
    'error',
])]
class DeliveryAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryAttemptFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
        ];
    }
}
