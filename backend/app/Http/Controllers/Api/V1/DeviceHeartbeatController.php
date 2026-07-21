<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeviceHeartbeatRequest;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Device;
use App\Services\Gateway\DeviceHeartbeatService;

final class DeviceHeartbeatController extends Controller
{
    public function __construct(
        private readonly DeviceHeartbeatService $heartbeats,
    ) {}

    public function __invoke(DeviceHeartbeatRequest $request): DeviceResource
    {
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device, 500);

        /** @var array{app_version: string, android_version: string, battery_percent: int, connection_type: string, fcm_token: string|null, sims: list<array{slot_index: int, carrier_name: string|null, phone_number: string|null, is_active: bool}>} $data */
        $data = $request->validated();

        return new DeviceResource($this->heartbeats->record($device, $data));
    }
}
