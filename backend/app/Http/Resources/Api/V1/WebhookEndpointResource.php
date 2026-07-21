<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebhookEndpoint */
final class WebhookEndpointResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'url' => $this->url, 'events' => $this->events, 'is_active' => $this->is_active, 'secret_hint' => 'htsms_whsec_'.$this->secret_prefix.'••••', 'consecutive_failures' => $this->consecutive_failures, 'disabled_at' => $this->disabled_at?->toIso8601String(), 'created_at' => $this->created_at?->toIso8601String()];
    }
}
