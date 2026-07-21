<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Marketing\CampaignStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $content
 * @property CampaignStatus $status
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $launched_at
 * @property int $recipient_count
 * @property int $suppressed_count
 */
final class Campaign extends Model
{
    use HasUlids;

    protected $fillable = ['organization_id', 'name', 'content', 'status', 'scheduled_at', 'launched_at', 'recipient_count', 'suppressed_count'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<CampaignRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    protected function casts(): array
    {
        return ['status' => CampaignStatus::class, 'scheduled_at' => 'immutable_datetime', 'launched_at' => 'immutable_datetime', 'recipient_count' => 'integer', 'suppressed_count' => 'integer'];
    }
}
