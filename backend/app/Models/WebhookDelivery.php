<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $webhook_endpoint_id
 * @property string $event_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempt_count
 * @property int|null $response_status
 * @property string|null $response_excerpt
 * @property CarbonImmutable|null $last_attempt_at
 * @property CarbonImmutable|null $delivered_at
 */
final class WebhookDelivery extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['webhook_endpoint_id', 'event_id', 'event_type', 'payload', 'status', 'attempt_count', 'response_status', 'response_excerpt', 'last_attempt_at', 'delivered_at'];

    /** @return BelongsTo<WebhookEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array', 'attempt_count' => 'integer', 'response_status' => 'integer', 'last_attempt_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }
}
