<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Models\User;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WebPasswordResetController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->string('email')->trim()->lower()->toString();
        Password::sendResetLink(['email' => $email]);
        $this->audit->record(SecurityEvent::PasswordResetRequested, $request, metadata: [
            'email_fingerprint' => $this->audit->emailFingerprint($email),
        ]);

        return back()->with('status', 'If that account exists, a reset link is on its way. Check your inbox and spam folder.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password', 'password_confirmation', 'token']);
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
                $this->audit->record(SecurityEvent::PasswordResetCompleted, $request, $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => ['The reset link is invalid or has expired. Request a new one.']]);
        }

        return redirect()->route('login')->with('status', 'Password updated. Sign in with your new password.');
    }
}
