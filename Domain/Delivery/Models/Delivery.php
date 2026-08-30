<?php

declare(strict_types=1);

namespace Domain\Delivery\Models;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'endpoint_event_id',
    'destination_id',
    'status',
    'attempts',
    'next_attempt_at',
    'last_status_code',
    'last_error',
    'locked_at',
])]
class Delivery extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EndpointEvent, $this>
     */
    public function endpointEvent(): BelongsTo
    {
        return $this->belongsTo(EndpointEvent::class);
    }

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    /**
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    /**
     * @return HasOne<DeliveryAttempt, $this>
     */
    public function latestDeliveryAttempt(): HasOne
    {
        return $this->hasOne(DeliveryAttempt::class)->latestOfMany('attempt_number');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'next_attempt_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
