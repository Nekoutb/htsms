<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Domain\Identity\OrganizationRole;
use App\DTO\Identity\CreateOrganizationData;
use App\Models\DeveloperApiKey;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Services\Identity\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_workspace_receives_trial_subscription(): void
    {
        $organization = app(OrganizationService::class)->createForOwner(
            new CreateOrganizationData('Trial Business', 'trial-business', 'Africa/Douala', 'en'), User::factory()->create(),
        );
        $subscription = $organization->subscription()->sole();
        self::assertSame('trial', $subscription->plan);
        self::assertSame('trialing', $subscription->status);
        self::assertTrue($subscription->trial_ends_at?->isFuture());
    }

    public function test_quota_is_atomic_and_idempotent_replay_is_not_charged_twice(): void
    {
        config()->set('htsms.plans.trial.messages', 1);
        [$credential, $key] = $this->apiKey();
        $payload = ['to' => '+237670000005', 'content' => 'Plan controlled message'];

        $this->withToken($credential)->withHeader('Idempotency-Key', 'billing-one')->postJson('/api/v1/messages', $payload)->assertCreated();
        $this->withToken($credential)->withHeader('Idempotency-Key', 'billing-one')->postJson('/api/v1/messages', $payload)->assertOk();
        $this->withToken($credential)->withHeader('Idempotency-Key', 'billing-two')->postJson('/api/v1/messages', $payload)
            ->assertStatus(402)->assertJsonPath('message', 'The message allowance for this billing period has been reached.');

        self::assertSame(1, Message::query()->count());
        self::assertSame(1, $key->organization->subscription()->sole()->messages_used);
    }

    public function test_paused_workspace_cannot_submit_new_work(): void
    {
        [$credential, $key] = $this->apiKey();
        $key->organization->forceFill(['sending_paused_at' => now()])->save();
        $this->withToken($credential)->postJson('/api/v1/messages', [
            'to' => '+237670000006', 'content' => 'Should be blocked',
        ])->assertStatus(402)->assertJsonPath('message', 'Sending is paused for this workspace.');
    }

    /** @return array{string, DeveloperApiKey} */
    private function apiKey(): array
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);
        $credential = 'htsms_live_testprefix_'.bin2hex(random_bytes(32));
        $key = $organization->developerApiKeys()->create([
            'created_by_user_id' => $owner->id, 'name' => 'Billing test', 'prefix' => substr(hash('sha256', $credential), 0, 12),
            'secret_hash' => hash('sha256', $credential), 'abilities' => ['messages:write'],
        ]);

        return [$credential, $key];
    }
}
