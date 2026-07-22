<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PairDeviceRequest;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\Gateway\DevicePairingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class DevicePairingController extends Controller
{
    public function __construct(
        private readonly DevicePairingService $pairing,
    ) {}

    public function challenge(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageDevices', $organization);
        /** @var User $user */
        $user = $request->user();
        $issued = $this->pairing->createChallenge($organization, $user);

        return response()->json([
            'data' => [
                'id' => $issued->challenge->getKey(),
                'pairing_token' => $issued->plainTextToken,
                'pairing_code' => $issued->shortCode,
                'pairing_uri' => 'htsms://pair?code='.$issued->shortCode,
                'expires_at' => $issued->challenge->expires_at->toIso8601String(),
            ],
            'meta' => ['message' => 'Scan the QR code or enter the 8-character code within 10 minutes. It can be used once.'],
        ], Response::HTTP_CREATED);
    }

    public function pair(PairDeviceRequest $request): JsonResponse
    {
        /** @var array{name: string, manufacturer: string, model: string, android_version: string, app_version: string, fcm_token: string|null, sims: list<array{slot_index: int, carrier_name: string|null, phone_number: string|null, is_active: bool}>} $deviceData */
        $deviceData = $request->safe()->except('pairing_token');
        $paired = $this->pairing->pair($request->string('pairing_token')->toString(), $deviceData);

        return response()->json([
            'data' => [
                'device' => (new DeviceResource($paired->device))->resolve($request),
                'device_credential' => $paired->plainTextCredential,
            ],
            'meta' => ['message' => 'Device paired. Store this credential securely; it will not be shown again.'],
        ], Response::HTTP_CREATED);
    }
}
