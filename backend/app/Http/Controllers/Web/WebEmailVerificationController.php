<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Identity\MagicLinkService;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Check your email" screen shown after registering. A signed-in user is
 * already verified (signing in requires clicking a magic link), so this only
 * serves accounts that have registered but not yet followed their link.
 */
final class WebEmailVerificationController extends Controller
{
    public function __construct(
        private readonly MagicLinkService $magicLinks,
        private readonly SecurityAuditService $audit,
    ) {}

    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user() instanceof User) {
            return redirect()->route('portal.home');
        }

        $email = $request->session()->get('pending_verification_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('login');
        }

        return view('auth.verify-email', ['email' => $email]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('pending_verification_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('login');
        }

        $this->magicLinks->sendTo($email, $request);
        $this->audit->record(SecurityEvent::MagicLinkRequested, $request, metadata: [
            'email_fingerprint' => $this->audit->emailFingerprint($email),
        ]);

        return redirect()->route('verification.notice')
            ->with('status', 'Sign-in link sent again. Check your inbox and spam folder.');
    }
}
