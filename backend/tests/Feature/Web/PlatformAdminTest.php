<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminMfaCode;
use App\Services\Billing\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_customer_cannot_access_platform_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_owner_requests_plan_and_admin_activates_it(): void
    {
        [$owner, $organization] = $this->workspace();
        app(SubscriptionService::class)->current($organization);
        $this->actingAs($owner)->post("/app/{$organization->id}/billing/requests", ['plan' => 'starter'])
            ->assertRedirect();
        $change = $organization->subscriptionChangeRequests()->sole();
        self::assertSame('pending', $change->status);

        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin)->withSession($this->verifiedSession($admin))->get('/admin')->assertOk()->assertSee($organization->name);
        $this->actingAs($admin)->withSession($this->verifiedSession($admin))->post("/admin/subscription-requests/{$change->id}/approve")
            ->assertRedirect();

        self::assertSame('approved', $change->refresh()->status);
        self::assertSame($admin->id, $change->reviewed_by_user_id);
        self::assertSame('starter', $organization->subscription()->sole()->plan);
        self::assertSame('active', $organization->subscription()->sole()->status);
    }

    public function test_admin_can_pause_and_suspend_workspace(): void
    {
        [, $organization] = $this->workspace();
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)->withSession($this->verifiedSession($admin))->post("/admin/organizations/{$organization->id}/pause")->assertRedirect();
        self::assertNotNull($organization->refresh()->sending_paused_at);
        $this->actingAs($admin)->withSession($this->verifiedSession($admin))->post("/admin/organizations/{$organization->id}/suspend")->assertRedirect();
        self::assertNotNull($organization->refresh()->suspended_at);
    }

    public function test_admin_can_toggle_inbound_and_outbound_channels(): void
    {
        [, $organization] = $this->workspace();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $session = $this->verifiedSession($admin);

        $this->actingAs($admin)->withSession($session)
            ->post("/admin/organizations/{$organization->id}/channels/inbound")->assertRedirect();
        $this->actingAs($admin)->withSession($session)
            ->post("/admin/organizations/{$organization->id}/channels/outbound")->assertRedirect();

        self::assertFalse($organization->refresh()->inbound_enabled);
        self::assertFalse($organization->refresh()->outbound_enabled);
    }

    public function test_admin_can_onboard_and_delete_only_their_customer(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $session = $this->verifiedSession($admin);
        $this->actingAs($admin)->withSession($session)->post('/admin/users', [
            'name' => 'Invited Customer',
            'email' => 'invited@example.com',
            'business_name' => 'Invited Business',
            'locale' => 'fr',
        ])->assertRedirect();

        $customer = User::query()->where('email', 'invited@example.com')->sole();
        self::assertSame($admin->id, $customer->onboarded_by_user_id);
        self::assertSame('free', $customer->organizations()->sole()->subscription()->sole()->plan);

        $this->actingAs($admin)->withSession($session)
            ->delete("/admin/users/{$customer->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_admin_requires_single_use_email_challenge(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.mfa.show'));
        $this->actingAs($admin)->post('/admin/mfa/send')->assertRedirect();
        $code = null;
        Notification::assertSentTo($admin, AdminMfaCode::class, function (AdminMfaCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });
        self::assertIsString($code);

        $this->actingAs($admin)->post('/admin/mfa/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/admin/mfa/verify', ['code' => $code])->assertRedirect(route('admin.index'));
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->post('/admin/mfa/verify', ['code' => $code])->assertSessionHasErrors('code');
    }

    /** @return array<string, int> */
    private function verifiedSession(User $admin): array
    {
        return ['platform_admin_mfa_verified_at' => now()->getTimestamp(), 'platform_admin_mfa_user_id' => $admin->id];
    }

    /** @return array{User, Organization} */
    private function workspace(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        return [$owner, $organization];
    }
}
