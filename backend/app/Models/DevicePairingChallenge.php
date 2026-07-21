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
 * @property string $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 */
final class DevicePairingChallenge extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'token_hash',
        'expires_at',
        'consumed_at',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
