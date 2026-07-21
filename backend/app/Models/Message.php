<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Messaging\MessageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $organization_id
 * @property string|null $device_id
 * @property string|null $device_sim_slot_id
 * @property string $recipient
 * @property string $body
 * @property MessageStatus $status
 * @property string|null $idempotency_key
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $assigned_at
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $expires_at
 * @property int $attempt_count
 * @property string|null $failure_code
 * @property string|null $failure_message
 */
final class Message extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'organization_id', 'device_id', 'device_sim_slot_id', 'recipient', 'body', 'status',
        'idempotency_key', 'scheduled_at', 'assigned_at', 'sent_at', 'delivered_at',
        'expires_at', 'attempt_count', 'failure_code', 'failure_message',
    ];

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

    /** @return HasMany<MessageEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'scheduled_at' => 'immutable_datetime',
            'assigned_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
        ];
    }
}
