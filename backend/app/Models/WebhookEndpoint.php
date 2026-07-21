<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $url
 * @property string $signing_secret
 * @property string $secret_prefix
 * @property list<string> $events
 * @property bool $is_active
 * @property int $consecutive_failures
 * @property CarbonImmutable|null $disabled_at
 */
final class WebhookEndpoint extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['organization_id', 'name', 'url', 'signing_secret', 'secret_prefix', 'events', 'is_active', 'consecutive_failures', 'disabled_at'];

    /** @var list<string> */
    protected $hidden = ['signing_secret'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['signing_secret' => 'encrypted', 'events' => 'array', 'is_active' => 'boolean', 'consecutive_failures' => 'integer', 'disabled_at' => 'immutable_datetime'];
    }
}
