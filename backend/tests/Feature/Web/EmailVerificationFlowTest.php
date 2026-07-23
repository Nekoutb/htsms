<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_flows_into_the_verification_notice_with_resend(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nadia Onboarder',
            'email' => 'nadia@example.com',
            'password' => 'Correct-Horse-99!',
            'password_confirmation' => 'Correct-Horse-99!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertGuest();
        $user = User::query()->where('email', 'nadia@example.com')->sole();
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/email/verify')
            ->assertOk()
            ->assertSee('Check your inbox')
            ->assertSee('nadia@example.com')
            ->assertSee('Resend verification email');

        $this->post('/email/verification-notification')
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_unverified_sign_in_reaches_the_notice_only_with_the_correct_password(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Correct-Horse-99!']);

        $this->post('/login', ['email' => $user->email, 'password' => 'totally-wrong'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-99!'])
            ->assertRedirect(route('verification.notice'));
        $this->assertGuest();

        $this->get('/email/verify')->assertOk()->assertSee($user->email);
    }

    public function test_plain_guests_learn_nothing_from_the_notice_page(): void
    {
        $this->get('/email/verify')->assertRedirect(route('login'));
        $this->post('/email/verification-notification')->assertRedirect(route('login'));
    }

    public function test_notice_redirects_once_the_email_is_verified(): void
    {
        $user = User::factory()->create();

        $this->withSession(['pending_verification_user_id' => $user->getKey()])
            ->get('/email/verify')
            ->assertRedirect(route('login', ['verified' => 1]));
    }

    public function test_verified_users_still_sign_in_straight_to_the_portal(): void
    {
        $user = User::factory()->create(['password' => 'Correct-Horse-99!']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-99!'])
            ->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($user);
    }
}
