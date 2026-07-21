<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Services\Identity\AuthenticationService;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class WebAuthenticationController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly SecurityAuditService $audit,
    ) {}

    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authentication->register($request->toData());
        $user->sendEmailVerificationNotification();
        $this->audit->record(SecurityEvent::Registered, $request, $user);
        $this->audit->record(SecurityEvent::VerificationSent, $request, $user);

        return redirect()->route('login')->with('status', 'Account created. Check your email to verify it before signing in.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
        ]);
        $email = Str::lower(trim((string) $credentials['email']));
        $throttleKey = hash('sha256', $email.'|'.($request->ip() ?? 'unknown'));
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages(['email' => ['Too many sign-in attempts. Try again shortly.']]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password'], 'email_verified_at' => fn ($query) => $query->whereNotNull('email_verified_at')], false)) {
            RateLimiter::hit($throttleKey, 60);
            $this->audit->record(SecurityEvent::LoginFailed, $request, metadata: ['email_fingerprint' => $this->audit->emailFingerprint($email)]);
            throw ValidationException::withMessages(['email' => ['The supplied credentials are invalid or the email is not verified.']]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user = $request->user();
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
