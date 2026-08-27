<?php

declare(strict_types=1);

namespace Domain\Endpoint\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, string> $headers
 */
#[Fillable(['endpoint_id', 'deduplication_key', 'headers', 'payload'])]
class EndpointEvent extends Model
{
    /** @use HasFactory<\Database\Factories\EndpointEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
        ];
    }
}
