<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Device;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_switch_persists_locale_and_returns_to_previous_page(): void
    {
        $this->from('/')->get('/language/fr')
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'fr');

        $this->get('/')->assertOk()
            ->assertSee('lang="fr"', false)
            ->assertSee('Votre téléphone Android devient une');
    }

    public function test_marketing_and_authentication_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Your Android phone is now an');
        $this->get('/login')->assertOk()->assertSee('Sign in to HTSMS');
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_devices_page_includes_android_installation_and_download(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->get("/app/{$organization->getKey()}/devices")
            ->assertOk()
            ->assertSee('Connect your phone in three steps')
            ->assertSee(config('htsms.apk.path'), false)
            ->assertSee('Scan QR code')
            ->assertSee('Android 8.0 or newer.');
    }

    public function test_guest_is_redirected_and_member_can_view_dashboard(): void
    {
        [$user, $organization] = $this->membership();
        $this->get("/app/{$organization->getKey()}")->assertRedirect('/login');

        $this->actingAs($user)->get("/app/{$organization->getKey()}")
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee('Latest messages');
    }

    public function test_member_cannot_access_another_organization(): void
    {
        [$user] = $this->membership();
        $other = Organization::factory()->create();

        $this->actingAs($user)->get("/app/{$other->getKey()}")->assertForbidden();
        $this->actingAs($user)->get("/app/{$other->getKey()}/devices")->assertForbidden();
        $this->actingAs($user)->get("/app/{$other->getKey()}/developer")->assertForbidden();
    }

    public function test_dashboard_can_queue_message_with_csrf_protected_form(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->post("/app/{$organization->getKey()}/messages", [
            'to' => '+237670000002',
            'content' => 'Your appointment is confirmed.',
        ])->assertRedirect(route('portal.messages', $organization));

        $message = Message::query()->sole();
        self::assertSame($organization->getKey(), $message->organization_id);
        self::assertSame('Your appointment is confirmed.', $message->body);
        self::assertSame(1, $message->events()->count());
    }

    public function test_only_administrator_can_create_one_time_pairing_secret(): void
    {
        [$owner, $organization] = $this->membership();
        $response = $this->actingAs($owner)->post("/app/{$organization->getKey()}/devices/pair")
            ->assertRedirect(route('portal.devices', $organization));
        self::assertIsString($response->getSession()->get('pairing_token'));
        self::assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $response->getSession()->get('pairing_code'));
        self::assertStringStartsWith('htsms://pair?code=', $response->getSession()->get('pairing_uri'));
        self::assertStringContainsString('&host=', $response->getSession()->get('pairing_uri'));

        [$developer, $developerOrganization] = $this->membership(OrganizationRole::Developer);
        $this->actingAs($developer)->post("/app/{$developerOrganization->getKey()}/devices/pair")
            ->assertForbidden();
    }

    public function test_owner_can_remove_device_without_deleting_its_record(): void
    {
        [$owner, $organization] = $this->membership();
        $device = Device::factory()->for($organization)->create();

        $this->actingAs($owner)->delete("/app/{$organization->getKey()}/devices/{$device->getKey()}")
            ->assertRedirect()
            ->assertSessionHas('status');

        self::assertNotNull($device->refresh()->revoked_at);
        $this->actingAs($owner)->get("/app/{$organization->getKey()}/devices")
            ->assertOk()
            ->assertDontSee($device->name);
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
}
