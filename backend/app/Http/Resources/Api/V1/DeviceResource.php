<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Device */
final class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'android_version' => $this->android_version,
            'app_version' => $this->app_version,
            'battery_percent' => $this->battery_percent,
            'connection_type' => $this->connection_type,
            'online' => $this->isOnline(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'sim_slots' => $this->whenLoaded('simSlots', fn (): array => $this->simSlots->map(fn ($sim): array => [
                'slot_index' => $sim->slot_index,
                'carrier_name' => $sim->carrier_name,
                'phone_number' => $sim->phone_number,
                'is_active' => $sim->is_active,
            ])->all()),
        ];
    }
}
