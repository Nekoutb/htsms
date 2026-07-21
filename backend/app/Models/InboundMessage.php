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
 * @property string $device_id
 * @property string|null $device_sim_slot_id
 * @property string $device_event_id
 * @property string $sender
 * @property string|null $recipient
 * @property string $body
 * @property CarbonImmutable $received_at
 */
final class InboundMessage extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['organization_id', 'device_id', 'device_sim_slot_id', 'device_event_id', 'sender', 'recipient', 'body', 'received_at'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Device, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['received_at' => 'immutable_datetime'];
    }
}
