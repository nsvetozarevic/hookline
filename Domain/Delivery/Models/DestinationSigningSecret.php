<?php

declare(strict_types=1);

namespace Domain\Delivery\Models;

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
#[Fillable(['destination_id', 'secret', 'expires_at'])]
#[Hidden(['secret'])]
class DestinationSigningSecret extends Model
{
    /** @use HasFactory<\Database\Factories\DestinationSigningSecretFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
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
