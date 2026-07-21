<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\InboundMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InboundMessage */
final class InboundMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'device_id' => $this->device_id, 'sender' => $this->sender, 'recipient' => $this->recipient, 'content' => $this->body, 'received_at' => $this->received_at->toIso8601String()];
    }
}
