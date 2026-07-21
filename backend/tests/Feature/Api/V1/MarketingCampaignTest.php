<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\DeveloperApiKey;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class MarketingCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_are_tenant_scoped_and_opt_out_creates_permanent_suppression(): void
    {
        [$credential, $key] = $this->apiKey();
        $this->withToken($credential)->postJson('/api/v1/contacts', [
            'phone' => '+237670000011', 'name' => 'Ada',
            'consent_status' => 'opted_out',
        ])->assertCreated()->assertJsonPath('data.consent_status', 'opted_out');

        $this->assertDatabaseHas('suppressions', [
            'organization_id' => $key->organization_id,
            'phone' => '+237670000011',
            'reason' => 'contact_opt_out',
        ]);
        $this->withToken($credential)->getJson('/api/v1/contacts')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_campaign_snapshots_only_consented_unsuppressed_contacts(): void
    {
        Queue::fake();
        [$credential] = $this->apiKey();
        $eligible = $this->contact($credential, '+237670000021', 'consented');
        $unknown = $this->contact($credential, '+237670000022', 'unknown');
        $optedOut = $this->contact($credential, '+237670000023', 'opted_out');

        $this->withToken($credential)->postJson('/api/v1/campaigns', [
            'name' => 'July launch',
            'content' => 'Our July offer is available. Reply STOP to opt out.',
            'contact_ids' => [$eligible, $unknown, $optedOut],
        ])->assertCreated()
            ->assertJsonPath('data.recipient_count', 1)
            ->assertJsonPath('data.suppressed_count', 2);

        $this->assertDatabaseCount('campaign_recipients', 3);
        $this->assertDatabaseCount('messages', 0);
        Queue::assertCount(1);
    }

    public function test_campaign_worker_uses_idempotent_message_queue(): void
    {
        [$credential] = $this->apiKey();
        $contact = $this->contact($credential, '+237670000031', 'consented');
        $this->withToken($credential)->postJson('/api/v1/campaigns', [
            'name' => 'Service update', 'content' => 'Service window starts at 22:00.',
            'contact_ids' => [$contact],
        ])->assertCreated();

        self::assertSame(1, Message::query()->count());
        $this->assertDatabaseHas('campaign_recipients', ['phone' => '+237670000031', 'status' => 'queued']);
        $this->assertDatabaseHas('campaigns', ['status' => 'queued']);
    }

    private function contact(string $credential, string $phone, string $status): string
    {
        $response = $this->withToken($credential)->postJson('/api/v1/contacts', [
            'phone' => $phone,
            'consent_status' => $status,
            'consent_source' => $status === 'consented' ? 'website checkout' : null,
        ])->assertCreated();
        $id = $response->json('data.id');
        self::assertIsString($id);

        return $id;
    }

    /** @return array{string, DeveloperApiKey} */
    private function apiKey(): array
    {
        $organization = Organization::factory()->create();
        $plainText = 'htsms_live_marketing_'.bin2hex(random_bytes(32));
        $key = $organization->developerApiKeys()->create([
            'created_by_user_id' => User::factory()->create()->getKey(),
            'name' => 'Marketing test key',
            'prefix' => substr(hash('sha256', $plainText), 0, 12),
            'secret_hash' => hash('sha256', $plainText),
            'abilities' => ['contacts:read', 'contacts:write', 'campaigns:read', 'campaigns:write'],
        ]);

        return [$plainText, $key];
    }
}
