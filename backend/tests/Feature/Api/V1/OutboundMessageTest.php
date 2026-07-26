<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Messaging\MessageStatus;
use App\Models\DeveloperApiKey;
use App\Models\Device;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OutboundMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_is_tenant_scoped_and_idempotent(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write', 'messages:read']);
        $payload = ['to' => '+237670000001', 'content' => 'Your HTSMS verification code is 123456.'];

        $first = $this->withToken($apiCredential)->withHeader('Idempotency-Key', 'order-1001')
            ->postJson('/api/v1/messages', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'queued');
        $second = $this->withToken($apiCredential)->withHeader('Idempotency-Key', 'order-1001')
            ->postJson('/api/v1/messages', $payload)
            ->assertOk();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        self::assertSame(1, Message::query()->where('organization_id', $apiKey->organization_id)->count());
        self::assertSame(1, Message::query()->sole()->events()->count());
    }

    public function test_message_list_can_be_filtered_by_status(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write', 'messages:read']);
        $apiKey->organization->messages()->create(['recipient' => '+237670000002', 'body' => 'Done', 'status' => MessageStatus::Delivered]);
        $apiKey->organization->messages()->create(['recipient' => '+237670000003', 'body' => 'Broke', 'status' => MessageStatus::Failed]);

        $this->withToken($apiCredential)->getJson('/api/v1/messages?status=delivered')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'delivered');

        $this->withToken($apiCredential)->getJson('/api/v1/messages?status=not-a-status')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_assigned_device_can_drive_message_through_delivery(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write']);
        [$deviceCredential, $device] = $this->device($apiKey->organization);
        $messageId = $this->withToken($apiCredential)->postJson('/api/v1/messages', [
            'to' => '+237690000001',
            'content' => 'Hello from HTSMS',
        ])->assertCreated()->json('data.id');
        self::assertIsString($messageId);

        $this->withToken($deviceCredential)->postJson('/api/v1/device/messages/lease')
            ->assertOk()
            ->assertJsonPath('data.id', $messageId)
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.device_id', $device->id);

        foreach (['dispatched', 'sent', 'delivered'] as $status) {
            $this->withToken($deviceCredential)
                ->postJson("/api/v1/device/messages/{$messageId}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $message = Message::query()->findOrFail($messageId);
        self::assertSame(MessageStatus::Delivered, $message->status);
        self::assertNotNull($message->sent_at);
        self::assertNotNull($message->delivered_at);
        self::assertSame(5, $message->events()->count());
    }

    public function test_preferred_sim_slot_is_stored_and_leased_to_the_device(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write']);
        [$deviceCredential] = $this->device($apiKey->organization);

        $messageId = $this->withToken($apiCredential)->postJson('/api/v1/messages', [
            'to' => '+237670000009',
            'content' => 'Send this from the second SIM.',
            'sim_slot' => 1,
        ])->assertCreated()->json('data.sim_slot_index');
        self::assertSame(1, Message::query()->sole()->preferred_sim_slot);

        $this->withToken($deviceCredential)->postJson('/api/v1/device/messages/lease')
            ->assertOk()
            ->assertJsonPath('data.sim_slot_index', 1);
    }

    public function test_invalid_sim_slot_is_rejected(): void
    {
        [$apiCredential] = $this->apiKey(['messages:write']);

        $this->withToken($apiCredential)->postJson('/api/v1/messages', [
            'to' => '+237670000009', 'content' => 'Bad slot', 'sim_slot' => 5,
        ])->assertUnprocessable()->assertJsonValidationErrors('sim_slot');
    }

    public function test_message_without_sim_preference_leases_a_null_slot(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write']);
        [$deviceCredential] = $this->device($apiKey->organization);
        $this->withToken($apiCredential)->postJson('/api/v1/messages', [
            'to' => '+237670000009', 'content' => 'No preference',
        ])->assertCreated()->assertJsonPath('data.sim_slot_index', null);

        $this->withToken($deviceCredential)->postJson('/api/v1/device/messages/lease')
            ->assertOk()
            ->assertJsonPath('data.sim_slot_index', null);
    }

    public function test_other_device_cannot_update_message_and_illegal_transition_conflicts(): void
    {
        [$apiCredential, $apiKey] = $this->apiKey(['messages:write']);
        [$assignedCredential] = $this->device($apiKey->organization);
        [$otherCredential] = $this->device($apiKey->organization);
        $messageId = $this->withToken($apiCredential)->postJson('/api/v1/messages', [
            'to' => '+237650000001', 'content' => 'Protected work item',
        ])->assertCreated()->json('data.id');
        self::assertIsString($messageId);
        $this->withToken($assignedCredential)->postJson('/api/v1/device/messages/lease')->assertOk();

        $this->withToken($otherCredential)
            ->postJson("/api/v1/device/messages/{$messageId}/status", ['status' => 'dispatched'])
            ->assertNotFound();
        $this->withToken($assignedCredential)
            ->postJson("/api/v1/device/messages/{$messageId}/status", ['status' => 'delivered'])
            ->assertConflict();
    }

    /** @param list<string> $abilities @return array{string, DeveloperApiKey} */
    private function apiKey(array $abilities): array
    {
        $organization = Organization::factory()->create();
        $plainText = 'htsms_live_testprefix_'.bin2hex(random_bytes(32));
        $key = $organization->developerApiKeys()->create([
            'created_by_user_id' => User::factory()->create()->getKey(),
            'name' => 'Messaging test key',
            'prefix' => substr(hash('sha256', $plainText), 0, 12),
            'secret_hash' => hash('sha256', $plainText),
            'abilities' => $abilities,
        ]);

        return [$plainText, $key];
    }

    /** @return array{string, Device} */
    private function device(Organization $organization): array
    {
        $device = Device::factory()->for($organization)->create();
        $plainText = 'htsms_device_testprefix_'.bin2hex(random_bytes(32));
        $device->credentials()->create([
            'prefix' => substr(hash('sha256', $plainText), 0, 12),
            'secret_hash' => hash('sha256', $plainText),
        ]);

        return [$plainText, $device];
    }
}
