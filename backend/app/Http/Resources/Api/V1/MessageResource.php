<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
final class MessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'to' => $this->recipient,
            'content' => $this->body,
            'status' => $this->status->value,
            'device_id' => $this->device_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'failure_code' => $this->failure_code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
