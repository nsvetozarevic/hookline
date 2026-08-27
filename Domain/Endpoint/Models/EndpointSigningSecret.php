<?php

declare(strict_types=1);

namespace Domain\Endpoint\Models;

use Database\Factories\EndpointSigningSecretFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $secret
 * @property Carbon|null $expires_at
 */
#[Fillable(['endpoint_id', 'secret', 'expires_at'])]
#[Hidden(['secret'])]
class EndpointSigningSecret extends Model
{
    /** @use HasFactory<EndpointSigningSecretFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    protected static function newFactory(): EndpointSigningSecretFactory
    {
        return EndpointSigningSecretFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
