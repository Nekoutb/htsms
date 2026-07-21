<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $device_id
 * @property string $prefix
 * @property string $secret_hash
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $revoked_at
 * @property-read Device $device
 */
final class DeviceCredential extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'device_id',
        'prefix',
        'secret_hash',
        'last_used_at',
        'revoked_at',
    ];

    /** @var list<string> */
    protected $hidden = ['secret_hash'];

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
