<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_strong_password_and_sends_verification(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'A',
            'email' => 'person@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'password']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Kissy Tester',
            'email' => 'Person@Example.com',
            'password' => 'Correct-Horse-99!',
            'password_confirmation' => 'Correct-Horse-99!',
        ])->assertCreated();

        $user = User::query()->where('email', 'person@example.com')->sole();
        self::assertTrue(Hash::check('Correct-Horse-99!', $user->password));
        self::assertStringContainsString('|', $response->json('data.verification_token'));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('security_audit_events', ['event' => SecurityEvent::Registered->value]);
    }

    public function test_login_uses_generic_failure_until_email_is_verified(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Correct-Horse-99!']);

        foreach (['wrong-password', 'Correct-Horse-99!'] as $password) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => $password,
                'device_name' => 'Dashboard',
            ])->assertUnprocessable()
                ->assertJsonPath('errors.email.0', 'The supplied credentials are invalid.');
        }

        $user->markEmailAsVerified();
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Correct-Horse-99!',
            'device_name' => 'Dashboard',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_signed_verification_marks_email_and_revokes_verification_token(): void
    {
        $user = User::factory()->unverified()->create();
        $user->createToken('email-verification', ['email:verify']);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson($url)->assertOk();

        self::assertTrue($user->fresh()?->hasVerifiedEmail());
        self::assertSame(0, $user->tokens()->count());
    }

    public function test_invalid_verification_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/v1/auth/email/verify/{$user->getKey()}/".sha1($user->email))
            ->assertForbidden();
    }

    public function test_forgot_password_does_not_reveal_account_existence_and_reset_revokes_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('existing-session', ['profile:read']);

        foreach (['unknown@example.com', $user->email] as $email) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('meta.message', 'If the account exists, a password reset email has been sent.');
        }
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::createToken($user);
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'New-Correct-Password-88!',
            'password_confirmation' => 'New-Correct-Password-88!',
        ])->assertOk();

        self::assertTrue(Hash::check('New-Correct-Password-88!', $user->fresh()?->password ?? ''));
        self::assertSame(0, $user->tokens()->count());
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
