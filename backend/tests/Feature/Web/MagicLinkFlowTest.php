<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\LoginLink;
use App\Models\User;
use App\Notifications\LoginLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class MagicLinkFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_passwordless_and_emails_a_link(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nadia Onboarder',
            'email' => 'Nadia@example.com',
        ])->assertRedirect(route('verification.notice'));

        $this->assertGuest();
        $user = User::query()->where('email', 'nadia@example.com')->sole();
        self::assertNull($user->email_verified_at);
        Notification::assertSentTo($user, LoginLinkNotification::class);

        $this->withSession(['pending_verification_email' => 'nadia@example.com'])
            ->get('/email/verify')->assertOk()
            ->assertSee('nadia@example.com')
            ->assertSee('Resend sign-in link');
    }

    public function test_login_request_is_neutral_and_sends_a_link_only_to_real_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/login', ['email' => 'ghost@example.com'])->assertRedirect(route('login.sent'));
        $this->post('/login', ['email' => $user->email])->assertRedirect(route('login.sent'));

        Notification::assertSentTimes(LoginLinkNotification::class, 1);
        $this->get('/login/check-email')->assertOk()->assertSee('Check your inbox');
    }

    public function test_a_valid_link_signs_the_user_in_and_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $this->issueToken($user);

        $this->get('/auth/magic/'.$token)->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs($user);
        self::assertNotNull($user->fresh()?->email_verified_at);
    }

    public function test_a_link_can_be_used_only_once(): void
    {
        $user = User::factory()->create();
        $token = $this->issueToken($user);

        $this->get('/auth/magic/'.$token)->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->get('/auth/magic/'.$token)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_expired_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $raw = bin2hex(random_bytes(32));
        LoginLink::query()->create([
            'email' => $user->email,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/auth/magic/'.$raw)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_only_the_token_hash_is_persisted(): void
    {
        $user = User::factory()->create();
        $raw = $this->issueToken($user);

        $this->assertDatabaseMissing('login_links', ['token_hash' => $raw]);
        $this->assertDatabaseHas('login_links', ['token_hash' => hash('sha256', $raw)]);
    }

    private function issueToken(User $user): string
    {
        $raw = bin2hex(random_bytes(32));
        LoginLink::query()->create([
            'email' => $user->email,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(15),
        ]);

        return $raw;
    }
}
