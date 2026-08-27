<?php

declare(strict_types=1);

namespace Domain\Endpoint\Models;

use Database\Factories\EndpointFactory;
use Domain\Endpoint\Policies\EndpointPolicy;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'capture_token', 'provider', 'is_active'])]
#[UsePolicy(EndpointPolicy::class)]
class Endpoint extends Model
{
    /** @use HasFactory<EndpointFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EndpointEvent, $this>
     */
    public function endpointEvents(): HasMany
    {
        return $this->hasMany(EndpointEvent::class);
    }

    /**
     * @return HasMany<EndpointSigningSecret, $this>
     */
    public function signingSecrets(): HasMany
    {
        return $this->hasMany(EndpointSigningSecret::class);
    }

    /**
     * @return HasMany<EndpointSigningSecret, $this>
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
     * @return HasOne<EndpointSigningSecret, $this>
     */
    public function currentSigningSecret(): HasOne
    {
        return $this->hasOne(EndpointSigningSecret::class)->whereNull('expires_at');
    }

    protected static function newFactory(): EndpointFactory
    {
        return EndpointFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
