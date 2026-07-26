<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Device;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Services\Integration\WebhookEndpointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PortalSettingsAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_renders_with_danger_zone_for_owner(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->get("/app/{$organization->getKey()}/settings")
            ->assertOk()
            ->assertSee('Danger zone')
            ->assertSee('Delete this workspace')
            ->assertSee('Delete my account');
    }

    public function test_profile_name_can_be_updated(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->post("/app/{$organization->getKey()}/settings/profile", ['name' => 'Renamed Owner'])
            ->assertRedirect(route('portal.settings', $organization));
        self::assertSame('Renamed Owner', $user->fresh()?->name);
    }

    public function test_workspace_language_can_be_changed_by_admin(): void
    {
        [$user, $organization] = $this->membership(OrganizationRole::Administrator);

        $this->actingAs($user)->post("/app/{$organization->getKey()}/settings/workspace", [
            'name' => $organization->name, 'locale' => 'fr',
        ])->assertRedirect(route('portal.settings', $organization));
        self::assertSame('fr', $organization->fresh()?->locale);
    }

    public function test_device_can_be_permanently_deleted(): void
    {
        [$user, $organization] = $this->membership();
        $device = Device::factory()->for($organization)->create();

        $this->actingAs($user)->delete("/app/{$organization->getKey()}/devices/{$device->getKey()}/delete")
            ->assertRedirect(route('portal.devices', $organization));
        self::assertModelMissing($device);
    }

    public function test_webhook_can_be_permanently_deleted(): void
    {
        [$user, $organization] = $this->membership();
        $endpoint = app(WebhookEndpointService::class)->create($organization, 'Order', 'https://8.8.8.8/hook', ['message.sent'])->endpoint;

        $this->actingAs($user)->delete("/app/{$organization->getKey()}/developer/webhooks/{$endpoint->getKey()}/delete")
            ->assertRedirect(route('portal.developer', $organization));
        self::assertSame(0, WebhookEndpoint::query()->count());
    }

    public function test_workspace_deletion_requires_matching_slug_confirmation(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->from("/app/{$organization->getKey()}/settings")
            ->delete("/app/{$organization->getKey()}/settings/workspace", ['confirm' => 'wrong'])
            ->assertRedirect("/app/{$organization->getKey()}/settings")
            ->assertSessionHasErrors('confirm');
        self::assertModelExists($organization);

        $this->actingAs($user)->delete("/app/{$organization->getKey()}/settings/workspace", ['confirm' => $organization->slug])
            ->assertRedirect(route('portal.home'));
        self::assertModelMissing($organization);
    }

    public function test_account_deletion_requires_correct_password_and_removes_solo_workspace(): void
    {
        $user = User::factory()->create(['password' => 'Correct-Horse-99!']);
        $organization = Organization::factory()->create();
        $organization->memberships()->create(['user_id' => $user->getKey(), 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        $this->actingAs($user)->from("/app/{$organization->getKey()}/settings")
            ->delete("/app/{$organization->getKey()}/settings/account", ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        self::assertModelExists($user);

        $this->actingAs($user)->delete("/app/{$organization->getKey()}/settings/account", ['password' => 'Correct-Horse-99!'])
            ->assertRedirect(route('home'));
        self::assertModelMissing($user);
        self::assertModelMissing($organization);
    }

    public function test_viewer_cannot_delete_workspace(): void
    {
        [$viewer, $organization] = $this->membership(OrganizationRole::Viewer);

        $this->actingAs($viewer)->delete("/app/{$organization->getKey()}/settings/workspace", ['confirm' => $organization->slug])
            ->assertForbidden();
        self::assertModelExists($organization);
    }

    /** @return array{User, Organization} */
    private function membership(OrganizationRole $role = OrganizationRole::Owner): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create(['user_id' => $user->getKey(), 'role' => $role, 'joined_at' => now()]);

        return [$user, $organization];
    }
}
