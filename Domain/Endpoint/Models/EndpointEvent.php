<?php

namespace Domain\Endpoint\Models;

use Database\Factories\EndpointEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, string> $headers
 * @property string $deduplication_key
 * @property string $payload
 */
#[Fillable(['endpoint_id', 'deduplication_key', 'headers', 'payload', 'received_at'])]
class EndpointEvent extends Model
{
    /** @use HasFactory<EndpointEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    protected static function newFactory(): EndpointEventFactory
    {
        return EndpointEventFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'received_at' => 'datetime',
        ];
    }
}
