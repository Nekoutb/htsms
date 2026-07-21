<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Device;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DeviceController extends Controller
{
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return DeviceResource::collection(
            $organization->devices()->with('simSlots')->latest()->paginate(20),
        );
    }

    public function revoke(Request $request, Organization $organization, Device $device): JsonResponse
    {
        abort_unless($device->organization_id === $organization->getKey(), 404);
        $this->authorize('revoke', $device);

        if ($device->revoked_at === null) {
            $device->forceFill(['revoked_at' => now()])->save();
            $device->credentials()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }

        return response()->json(['meta' => ['message' => 'Device revoked.']]);
    }
}
