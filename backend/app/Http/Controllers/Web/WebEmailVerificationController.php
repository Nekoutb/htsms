<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WebEmailVerificationController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function notice(Request $request): RedirectResponse|View
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedEmail()) {
            $request->session()->forget('pending_verification_user_id');

            return $request->user() !== null
                ? redirect()->route('portal.home')
                : redirect()->route('login', ['verified' => 1]);
        }

        return view('auth.verify-email', ['email' => $user->email]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            return redirect()->route('login');
        }
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->audit->record(SecurityEvent::VerificationSent, $request, $user);
        }

        return redirect()->route('verification.notice')
            ->with('status', 'Verification email sent again. Check your inbox and spam folder.');
    }

    /**
     * The pending account comes from the authenticated session or from the
     * short-lived marker set after registering or signing in with a correct
     * password; guests without either context reveal nothing.
     */
    private function pendingUser(Request $request): ?User
    {
        $authenticated = $request->user();
        if ($authenticated instanceof User) {
            return $authenticated;
        }
        $pendingId = $request->session()->get('pending_verification_user_id');

        return is_int($pendingId) || is_string($pendingId)
            ? User::query()->find($pendingId)
            : null;
    }
}
