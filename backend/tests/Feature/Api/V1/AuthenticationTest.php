<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Notifications\LoginLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_passwordless_and_emails_a_sign_in_link(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', ['name' => 'A', 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Kissy Tester',
            'email' => 'Person@Example.com',
        ])->assertCreated()
            ->assertJsonStructure(['data' => ['user'], 'meta' => ['message']]);

        $user = User::query()->where('email', 'person@example.com')->sole();
        self::assertNull($user->email_verified_at);
        Notification::assertSentTo($user, LoginLinkNotification::class);
        $this->assertDatabaseHas('security_audit_events', ['event' => SecurityEvent::Registered->value]);
    }

    public function test_requesting_a_link_never_reveals_whether_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        foreach (['unknown@example.com', $user->email] as $email) {
            $this->postJson('/api/v1/auth/login', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('meta.message', 'If an account exists for that email, a sign-in link is on its way.');
        }

        Notification::assertSentTo($user, LoginLinkNotification::class);
        Notification::assertSentTimes(LoginLinkNotification::class, 1);
        $this->assertDatabaseHas('security_audit_events', ['event' => SecurityEvent::MagicLinkRequested->value]);
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current', ['profile:read']);
        $user->createToken('other', ['profile:read']);

        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/v1/auth/logout')
            ->assertOk();

        self::assertSame(1, $user->tokens()->count());
        self::assertSame(SecurityEvent::LoggedOut, SecurityAuditEvent::query()->latest('created_at')->first()?->event);
    }
}
