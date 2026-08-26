<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\User;
use App\Services\Identity\AuthenticationService;
use App\Services\Identity\MagicLinkService;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WebAuthenticationController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly MagicLinkService $magicLinks,
        private readonly SecurityAuditService $audit,
    ) {}

    public function loginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Email a single-use sign-in link. The response is identical whether or not
     * an account exists, so it never reveals which emails are registered.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email:rfc', 'max:254']]);
        $email = Str::lower(trim((string) $validated['email']));

        $this->magicLinks->sendTo($email, $request);
        $this->audit->record(SecurityEvent::MagicLinkRequested, $request, metadata: [
            'email_fingerprint' => $this->audit->emailFingerprint($email),
        ]);

        return redirect()->route('login.sent');
    }

    public function linkSent(): View
    {
        return view('auth.magic-sent');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authentication->register($request->toData());
        $this->magicLinks->sendTo($user->email, $request);
        $this->audit->record(SecurityEvent::Registered, $request, $user);
        $this->audit->record(SecurityEvent::MagicLinkRequested, $request, $user);
        $request->session()->put('pending_verification_email', $user->email);

        return redirect()->route('verification.notice')
            ->with('status', 'Account created. We emailed you a sign-in link.');
    }

    /**
     * Consume a magic link, sign the user in, and start a fresh session.
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $user = $this->magicLinks->consume($token);
        if (! $user instanceof User) {
            return redirect()->route('login')->withErrors([
                'email' => 'This sign-in link is invalid or has expired. Request a new one.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget('pending_verification_email');
        $this->audit->record(SecurityEvent::MagicLinkConsumed, $request, $user);
        $this->audit->record(SecurityEvent::LoginSucceeded, $request, $user);

        return redirect()->intended(route('portal.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $this->audit->record(SecurityEvent::LoggedOut, $request, $user);

        return redirect()->route('home');
    }
}
