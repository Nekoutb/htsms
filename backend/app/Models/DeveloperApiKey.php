<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $organization_id
 * @property int $created_by_user_id
 * @property string $name
 * @property string $prefix
 * @property string $secret_hash
 * @property list<string> $abilities
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $last_used_at
 * @property-read Organization $organization
 */
final class DeveloperApiKey extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'name',
        'prefix',
        'secret_hash',
        'abilities',
        'expires_at',
        'revoked_at',
        'last_used_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'secret_hash',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
        ];
    }
}
