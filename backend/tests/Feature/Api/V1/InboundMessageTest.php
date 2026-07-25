<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use App\Models\Device;
use App\Models\InboundMessage;
use App\Models\Organization;
use App\Models\Suppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InboundMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_upload_is_tenant_scoped_and_idempotent(): void
    {
        [$credential, $device] = $this->device();
        $sim = $device->simSlots()->create(['slot_index' => 0, 'carrier_name' => 'Orange CM', 'phone_number' => '+237690000000', 'is_active' => true]);
        $payload = [
            'device_event_id' => 'sms-event-00000001', 'sender' => '+237670000004',
            'recipient' => '+237690000000', 'content' => 'YES', 'received_at' => now()->toIso8601String(), 'sim_slot_index' => 0,
        ];

        $first = $this->withToken($credential)->postJson('/api/v1/device/inbound-messages', $payload)
            ->assertCreated()->assertJsonPath('data.sender', '+237670000004');
        $second = $this->withToken($credential)->postJson('/api/v1/device/inbound-messages', $payload)->assertOk();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        self::assertSame(1, InboundMessage::query()->count());
        self::assertSame($device->organization_id, InboundMessage::query()->sole()->organization_id);
        self::assertSame($sim->getKey(), InboundMessage::query()->sole()->device_sim_slot_id);
        $this->assertDatabaseMissing('suppressions', ['organization_id' => $device->organization_id, 'phone' => '+237670000004']);
    }

    public function test_unknown_device_credential_is_rejected(): void
    {
        $this->withToken('htsms_device_unknown_'.str_repeat('0', 64))
            ->postJson('/api/v1/device/inbound-messages', [])->assertUnauthorized();
    }

    public function test_disabled_inbound_channel_rejects_device_upload(): void
    {
        [$credential, $device] = $this->device();
        $device->organization->forceFill(['inbound_enabled' => false])->save();

        $this->withToken($credential)->postJson('/api/v1/device/inbound-messages', [
            'device_event_id' => 'sms-disabled-0001',
            'sender' => '+237670000010',
            'content' => 'Blocked inbound',
            'received_at' => now()->toIso8601String(),
            'sim_slot_index' => 0,
        ])->assertForbidden()->assertJsonPath('message', 'Inbound messaging is disabled for this workspace.');

        self::assertSame(0, InboundMessage::query()->count());
    }

    public function test_stop_keyword_immediately_suppresses_sender(): void
    {
        [$credential, $device] = $this->device();
        Contact::query()->create([
            'organization_id' => $device->organization_id,
            'phone' => '+237670000099',
            'consent_status' => 'consented',
            'consent_source' => 'checkout',
            'consented_at' => now(),
        ]);

        $payload = [
            'device_event_id' => 'sms-stop-00000001',
            'sender' => '+237670000099',
            'content' => '  Arrêt! ',
            'received_at' => now()->toIso8601String(),
            'sim_slot_index' => 0,
        ];
        $this->withToken($credential)->postJson('/api/v1/device/inbound-messages', $payload)->assertCreated();

        $this->assertDatabaseHas('suppressions', [
            'organization_id' => $device->organization_id,
            'phone' => '+237670000099',
            'reason' => 'inbound_opt_out',
        ]);
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $device->organization_id,
            'phone' => '+237670000099',
            'consent_status' => 'opted_out',
        ]);

        Suppression::query()->delete();
        Contact::query()->where('phone', '+237670000099')->update(['consent_status' => 'consented', 'opted_out_at' => null]);
        $this->withToken($credential)->postJson('/api/v1/device/inbound-messages', $payload)->assertOk();
        $this->assertDatabaseHas('suppressions', ['phone' => '+237670000099', 'reason' => 'inbound_opt_out']);
    }

    /** @return array{string, Device} */
    private function device(): array
    {
        $device = Device::factory()->for(Organization::factory())->create();
        $credential = 'htsms_device_testprefix_'.bin2hex(random_bytes(32));
        $device->credentials()->create(['prefix' => substr(hash('sha256', $credential), 0, 12), 'secret_hash' => hash('sha256', $credential)]);

        return [$credential, $device];
    }
}
