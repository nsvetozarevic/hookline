<?php

declare(strict_types=1);

namespace Domain\Delivery\Models;

use Domain\Endpoint\Models\Endpoint;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property array<string, string>|null $headers
 */
#[Fillable(['endpoint_id', 'url', 'is_active', 'timeout_seconds', 'max_attempts', 'headers'])]
class Destination extends Model
{
    /** @use HasFactory<\Database\Factories\DestinationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    /**
     * @return HasMany<DestinationSigningSecret, $this>
     */
    public function signingSecrets(): HasMany
    {
        return $this->hasMany(DestinationSigningSecret::class);
    }

    /**
     * @return HasMany<DestinationSigningSecret, $this>
     */
    public function unexpiredSigningSecrets(): HasMany
    {
        return $this->signingSecrets()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @return HasOne<DestinationSigningSecret, $this>
     */
    public function currentSigningSecret(): HasOne
    {
        return $this->hasOne(DestinationSigningSecret::class)->whereNull('expires_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'headers' => 'array',
        ];
    }
}
