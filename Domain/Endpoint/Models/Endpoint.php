<?php

declare(strict_types=1);

namespace Domain\Endpoint\Models;

use Database\Factories\EndpointFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'capture_token', 'signing_secret', 'provider', 'is_active'])]
#[Hidden(['signing_secret'])]
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
