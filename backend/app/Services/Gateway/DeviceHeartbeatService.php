<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Device;

final readonly class DeviceHeartbeatService
{
    /**
     * @param  array{app_version: string, android_version: string, battery_percent: int, connection_type: string, fcm_token: string|null, sims: list<array{slot_index: int, carrier_name: string|null, phone_number: string|null, is_active: bool}>}  $data
     */
    public function record(Device $device, array $data): Device
    {
        $device->forceFill([
            'app_version' => $data['app_version'],
            'android_version' => $data['android_version'],
            'battery_percent' => $data['battery_percent'],
            'connection_type' => $data['connection_type'],
            // Nullable fields may be omitted entirely by older gateway builds;
            // validated() drops absent keys, so read them defensively.
            'fcm_token' => $data['fcm_token'] ?? null,
            'last_seen_at' => now(),
        ])->save();

        $reportedSlots = [];
        foreach ($data['sims'] as $sim) {
            $reportedSlots[] = $sim['slot_index'];
            $device->simSlots()->updateOrCreate(
                ['slot_index' => $sim['slot_index']],
                [
                    'carrier_name' => $sim['carrier_name'] ?? null,
                    'phone_number' => $sim['phone_number'] ?? null,
                    'is_active' => $sim['is_active'],
                ],
            );
        }

        $device->simSlots()->whereNotIn('slot_index', $reportedSlots)->delete();

        return $device->load('simSlots');
    }
}
