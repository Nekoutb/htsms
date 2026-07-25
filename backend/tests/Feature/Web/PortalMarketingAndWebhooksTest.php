<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Domain\Marketing\ConsentStatus;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class PortalMarketingAndWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_is_available_only_from_platform_admin(): void
    {
        [, $organization] = $this->membership();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $session = $this->verifiedSession($admin);

        $this->actingAs($admin)->withSession($session)->get('/admin')
            ->assertOk()
            ->assertSee('Marketing');

        $this->actingAs($admin)->withSession($session)->get("/admin/organizations/{$organization->getKey()}/marketing")
            ->assertOk()
            ->assertSee('Add a contact')
            ->assertSee('New campaign');
    }

    public function test_customer_portal_has_no_marketing_section(): void
    {
        [$owner, $organization] = $this->membership();

        $this->actingAs($owner)->get("/app/{$organization->getKey()}")
            ->assertOk()
            ->assertDontSee('Marketing');

        $this->actingAs($owner)->get("/app/{$organization->getKey()}/marketing")->assertNotFound();
    }

    public function test_admin_can_add_a_marketing_contact(): void
    {
        [, $organization] = $this->membership();
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->withSession($this->verifiedSession($admin))
            ->post("/admin/organizations/{$organization->getKey()}/marketing/contacts", [
                'phone' => '+237670000123',
                'name' => 'Ada Lovelace',
                'consent_status' => 'consented',
                'consent_source' => 'Web signup form',
            ])->assertRedirect(route('admin.marketing', $organization));

        $contact = Contact::query()->sole();
        self::assertSame('+237670000123', $contact->phone);
        self::assertSame(ConsentStatus::Consented, $contact->consent_status);
        self::assertNotNull($contact->consented_at);
    }

    public function test_campaign_launch_queues_only_consented_contacts(): void
    {
        Queue::fake();
        [, $organization] = $this->membership();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $consented = Contact::query()->create([
            'organization_id' => $organization->getKey(), 'phone' => '+237670000001',
            'consent_status' => ConsentStatus::Consented, 'consented_at' => now(),
        ]);
        $unknown = Contact::query()->create([
            'organization_id' => $organization->getKey(), 'phone' => '+237670000002',
            'consent_status' => ConsentStatus::Unknown,
        ]);

        $this->actingAs($admin)->withSession($this->verifiedSession($admin))
            ->post("/admin/organizations/{$organization->getKey()}/marketing/campaigns", [
                'name' => 'July promo',
                'content' => 'Hello from HTSMS',
                'contact_ids' => [$consented->id, $unknown->id],
            ])->assertRedirect(route('admin.marketing', $organization));

        $campaign = Campaign::query()->sole();
        self::assertSame(1, $campaign->recipient_count);
        self::assertSame(1, $campaign->suppressed_count);
    }

    public function test_owner_can_create_and_disable_a_webhook(): void
    {
        [$user, $organization] = $this->membership();

        $create = $this->actingAs($user)->post("/app/{$organization->getKey()}/developer/webhooks", [
            'name' => 'Order service',
            'url' => 'https://8.8.8.8/htsms',
            'events' => ['message.delivered', 'message.failed'],
        ])->assertRedirect(route('portal.developer', $organization));
        self::assertStringStartsWith('htsms_whsec_', (string) $create->getSession()->get('plain_text_webhook_secret'));

        $endpoint = WebhookEndpoint::query()->sole();
        self::assertTrue($endpoint->is_active);

        $this->actingAs($user)->delete("/app/{$organization->getKey()}/developer/webhooks/{$endpoint->getKey()}")
            ->assertRedirect();
        self::assertFalse($endpoint->fresh()?->is_active);
    }

    public function test_private_webhook_url_is_rejected_in_portal(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->from("/app/{$organization->getKey()}/developer")
            ->post("/app/{$organization->getKey()}/developer/webhooks", [
                'name' => 'Unsafe', 'url' => 'https://127.0.0.1/hook', 'events' => ['message.sent'],
            ])->assertRedirect("/app/{$organization->getKey()}/developer")
            ->assertSessionHasErrors('url');
        self::assertSame(0, WebhookEndpoint::query()->count());
    }

    public function test_developer_role_cannot_manage_webhooks(): void
    {
        [$developer, $organization] = $this->membership(OrganizationRole::Developer);

        $this->actingAs($developer)->post("/app/{$organization->getKey()}/developer/webhooks", [
            'name' => 'Order service', 'url' => 'https://8.8.8.8/htsms', 'events' => ['message.delivered'],
        ])->assertForbidden();
    }

    public function test_inbound_replies_render_on_messages_page(): void
    {
        [$user, $organization] = $this->membership();
        $device = Device::factory()->for($organization)->create();
        $organization->inboundMessages()->create([
            'device_id' => $device->getKey(),
            'device_event_id' => 'evt-'.bin2hex(random_bytes(8)),
            'sender' => '+237690000009',
            'body' => 'STOP',
            'received_at' => now(),
        ]);

        $this->actingAs($user)->get("/app/{$organization->getKey()}/messages")
            ->assertOk()
            ->assertSee('Recent replies')
            ->assertSee('+237690000009');
    }

    /** @return array{User, Organization} */
    private function membership(OrganizationRole $role = OrganizationRole::Owner): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => $role,
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }

    /** @return array<string, int> */
    private function verifiedSession(User $admin): array
    {
        return ['platform_admin_mfa_verified_at' => now()->getTimestamp(), 'platform_admin_mfa_user_id' => $admin->id];
    }
}
