<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\DTO\Gateway\IssuedPairingChallenge;
use App\DTO\Gateway\PairedDevice;
use App\Models\DevicePairingChallenge;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DevicePairingService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function createChallenge(Organization $organization, User $creator): IssuedPairingChallenge
    {
        $plainTextToken = 'htsms_pair_'.bin2hex(random_bytes(32));
        $challenge = $organization->devicePairingChallenges()->create([
            'created_by_user_id' => $creator->getKey(),
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addMinutes(10),
        ]);

        return new IssuedPairingChallenge($challenge, $plainTextToken);
    }

    /**
     * @param  array{name: string, manufacturer: string, model: string, android_version: string, app_version: string, fcm_token: string|null, sims: list<array{slot_index: int, carrier_name: string|null, phone_number: string|null, is_active: bool}>}  $data
     */
    public function pair(string $plainTextToken, array $data): PairedDevice
    {
        return DB::transaction(function () use ($plainTextToken, $data): PairedDevice {
            $challenge = DevicePairingChallenge::query()
                ->where('token_hash', hash('sha256', $plainTextToken))
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                throw (new ModelNotFoundException)->setModel(DevicePairingChallenge::class);
            }

            $organization = Organization::query()->findOrFail($challenge->organization_id);
            $this->subscriptions->ensureDeviceAvailable($organization);
            $device = $organization->devices()->create([
                'name' => $data['name'],
                'manufacturer' => $data['manufacturer'],
                'model' => $data['model'],
                'android_version' => $data['android_version'],
                'app_version' => $data['app_version'],
                'fcm_token' => $data['fcm_token'],
                'last_seen_at' => now(),
            ]);

            foreach ($data['sims'] as $sim) {
                $device->simSlots()->create($sim);
            }

            $prefix = Str::lower(Str::random(12));
            $secret = bin2hex(random_bytes(32));
            $plainTextCredential = "htsms_device_{$prefix}_{$secret}";
            $device->credentials()->create([
                'prefix' => $prefix,
                'secret_hash' => hash('sha256', $plainTextCredential),
            ]);
            $challenge->forceFill(['consumed_at' => now()])->save();

            return new PairedDevice($device->load('simSlots'), $plainTextCredential);
        });
    }
}
