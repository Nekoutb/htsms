<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $organization_id
 * @property string $id
 * @property string $name
 * @property string $manufacturer
 * @property string $model
 * @property string $android_version
 * @property string $app_version
 * @property string|null $fcm_token
 * @property int|null $battery_percent
 * @property string|null $connection_type
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $revoked_at
 */
final class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'name',
        'manufacturer',
        'model',
        'android_version',
        'app_version',
        'fcm_token',
        'battery_percent',
        'connection_type',
        'last_seen_at',
        'revoked_at',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<DeviceCredential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(DeviceCredential::class);
    }

    /**
     * @return HasMany<DeviceSimSlot, $this>
     */
    public function simSlots(): HasMany
    {
        return $this->hasMany(DeviceSimSlot::class);
    }

    public function isOnline(): bool
    {
        return $this->revoked_at === null
            && $this->last_seen_at?->isAfter(now()->subMinutes(2)) === true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fcm_token' => 'encrypted',
            'last_seen_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'battery_percent' => 'integer',
        ];
    }
}
