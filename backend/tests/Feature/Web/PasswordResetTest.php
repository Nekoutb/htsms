<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_a_working_forgot_password_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot your password?');
        $this->get('/forgot-password')->assertOk()->assertSee('Reset your password');
    }

    public function test_reset_email_builds_a_resolvable_web_reset_url(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        // Render the mail for real: this regresses if the password.reset route
        // disappears, which previously made every reset email throw a 500.
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $url = is_string($mail->actionUrl) ? $mail->actionUrl : '';

            return str_contains($url, '/reset-password/');
        });
    }

    public function test_unknown_email_receives_the_same_generic_response(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_through_the_web_form(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")
            ->assertOk()
            ->assertSee('Choose a new password');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Fresh-Correct-Horse-77!',
            'password_confirmation' => 'Fresh-Correct-Horse-77!',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        self::assertTrue(Hash::check('Fresh-Correct-Horse-77!', $user->fresh()?->password ?? ''));

        $this->post('/login', ['email' => $user->email, 'password' => 'Fresh-Correct-Horse-77!'])
            ->assertRedirect(route('portal.home'));
    }

    public function test_invalid_token_shows_a_friendly_error(): void
    {
        $user = User::factory()->create();

        $this->from('/reset-password/not-a-real-token')->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'Fresh-Correct-Horse-77!',
            'password_confirmation' => 'Fresh-Correct-Horse-77!',
        ])->assertRedirect('/reset-password/not-a-real-token')
            ->assertSessionHasErrors('email');
    }
}
