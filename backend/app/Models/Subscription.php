<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $plan
 * @property string $status
 * @property int $messages_used
 * @property CarbonImmutable|null $trial_ends_at
 * @property CarbonImmutable $current_period_starts_at
 * @property CarbonImmutable $current_period_ends_at
 * @property CarbonImmutable|null $grace_ends_at
 * @property CarbonImmutable|null $cancelled_at
 */
final class Subscription extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['organization_id', 'plan', 'status', 'messages_used', 'trial_ends_at', 'current_period_starts_at', 'current_period_ends_at', 'grace_ends_at', 'cancelled_at'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['messages_used' => 'integer', 'trial_ends_at' => 'immutable_datetime', 'current_period_starts_at' => 'immutable_datetime', 'current_period_ends_at' => 'immutable_datetime', 'grace_ends_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime'];
    }
}
