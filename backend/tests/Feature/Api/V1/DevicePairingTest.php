<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\OrganizationRole;
use App\Models\Device;
use App\Models\DeviceCredential;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DevicePairingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_one_time_pairing_challenge_and_pair_device(): void
    {
        [$user, $organization] = $this->owner();
        Sanctum::actingAs($user, ['devices:write']);
        $challenge = $this->postJson("/api/v1/organizations/{$organization->getKey()}/device-pairing-challenges")
            ->assertCreated();
        $token = $challenge->json('data.pairing_token');
        self::assertMatchesRegularExpression('/^htsms_pair_[A-HJ-NP-Z2-9]{8}$/', $token);
        self::assertSame(substr($token, 11), $challenge->json('data.pairing_code'));
        self::assertSame(
            'htsms://pair?code='.substr($token, 11).'&host='.rawurlencode(rtrim((string) config('app.url'), '/')),
            $challenge->json('data.pairing_uri'),
        );

        $pairing = $this->postJson('/api/v1/device/pair', $this->pairingPayload($token))
            ->assertCreated();

        $credential = $pairing->json('data.device_credential');
        self::assertIsString($credential);
        self::assertStringStartsWith('htsms_device_', $credential);
        self::assertSame($organization->getKey(), $pairing->json('data.device.organization_id'));
        self::assertSame(hash('sha256', $credential), DeviceCredential::query()->sole()->secret_hash);

        $this->postJson('/api/v1/device/pair', $this->pairingPayload($token))->assertNotFound();
    }

    public function test_expired_or_unknown_pairing_token_is_rejected(): void
    {
        $this->postJson('/api/v1/device/pair', $this->pairingPayload('htsms_pair_ABCD2345'))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_create_challenge(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Developer,
            'joined_at' => now(),
        ]);
        Sanctum::actingAs($user, ['devices:write']);

        $this->postJson("/api/v1/organizations/{$organization->getKey()}/device-pairing-challenges")
            ->assertForbidden();
    }

    public function test_authenticated_heartbeat_updates_device_and_sim_state(): void
    {
        [$credential, $device] = $this->pairedDevice();

        $this->withToken($credential)->postJson('/api/v1/device/heartbeat', [
            'app_version' => '1.1.0',
            'android_version' => '15',
            'battery_percent' => 62,
            // Gateway v0.2 sent Android's legacy typeName instead of the v0.3
            // normalized "cellular" value.
            'connection_type' => 'mobile',
            'fcm_token' => 'firebase-token',
            'sims' => [[
                'slot_index' => 0,
                'carrier_name' => 'MTN Cameroon',
                'phone_number' => '+237670000000',
                'is_active' => true,
            ]],
        ])->assertOk()
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.battery_percent', 62)
            ->assertJsonPath('data.sim_slots.0.carrier_name', 'MTN Cameroon');

        self::assertSame('1.1.0', $device->fresh()?->app_version);
        self::assertSame('mobile', $device->fresh()?->connection_type);
        self::assertSame('+237670000000', $device->simSlots()->sole()->phone_number);
    }

    public function test_revoking_device_immediately_rejects_credential(): void
    {
        [$credential, $device, $user, $organization] = $this->pairedDevice();
        Sanctum::actingAs($user, ['devices:write']);

        $this->deleteJson("/api/v1/organizations/{$organization->getKey()}/devices/{$device->getKey()}")
            ->assertOk();

        $this->withToken($credential)->postJson('/api/v1/device/heartbeat', [])->assertUnauthorized();
        self::assertNotNull($device->fresh()?->revoked_at);
        self::assertNotNull($device->credentials()->sole()->revoked_at);
    }

    /**
     * @return array{User, Organization}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Owner,
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }

    /**
     * @return array{string, Device, User, Organization}
     */
    private function pairedDevice(): array
    {
        [$user, $organization] = $this->owner();
        $device = Device::factory()->for($organization)->create();
        $credential = 'htsms_device_testprefix_'.bin2hex(random_bytes(32));
        $device->credentials()->create([
            'prefix' => substr(hash('sha256', $credential), 0, 12),
            'secret_hash' => hash('sha256', $credential),
        ]);

        return [$credential, $device, $user, $organization];
    }

    /**
     * @return array<string, mixed>
     */
    private function pairingPayload(string $token): array
    {
        return [
            'pairing_token' => $token,
            'name' => 'Office Gateway',
            'manufacturer' => 'Samsung',
            'model' => 'SM-A145F',
            'android_version' => '14',
            'app_version' => '1.0.0',
            'fcm_token' => null,
            'sims' => [[
                'slot_index' => 0,
                'carrier_name' => 'Orange Cameroun',
                'phone_number' => '+237690000000',
                'is_active' => true,
            ]],
        ];
    }
}
