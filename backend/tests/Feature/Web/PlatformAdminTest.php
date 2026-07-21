<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee($organization->name);
        $this->actingAs($admin)->post("/admin/subscription-requests/{$change->id}/approve")
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

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/pause")->assertRedirect();
        self::assertNotNull($organization->refresh()->sending_paused_at);
        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/suspend")->assertRedirect();
        self::assertNotNull($organization->refresh()->suspended_at);
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
