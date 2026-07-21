<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Campaign */
final class CampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'status' => $this->status->value,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'launched_at' => $this->launched_at?->toIso8601String(),
            'recipient_count' => $this->recipient_count,
            'suppressed_count' => $this->suppressed_count,
        ];
    }
}
