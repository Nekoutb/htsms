<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminMfaCode;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class AdminMfaController extends Controller
{
    public function __construct(private readonly SecurityAuditService $audit) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->admin($request);
        if ($this->isVerified($request, $user)) {
            return redirect()->route('admin.index');
        }

        return view('admin.mfa');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $this->admin($request);
        $code = (string) random_int(100000, 999999);
        $request->session()->put([
            'platform_admin_mfa_code_hash' => Hash::make($code),
            'platform_admin_mfa_code_expires_at' => now()->addMinutes(10)->getTimestamp(),
            'platform_admin_mfa_code_user_id' => $user->getKey(),
            'platform_admin_mfa_attempts' => 0,
        ]);
        $user->notify(new AdminMfaCode($code));
        $this->audit->record(SecurityEvent::AdminMfaChallengeSent, $request, $user);

        return back()->with('status', 'A verification code was sent to your email.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->admin($request);
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $hash = $request->session()->get('platform_admin_mfa_code_hash');
        $expiresAt = $request->session()->get('platform_admin_mfa_code_expires_at');
        $challengeUserId = $request->session()->get('platform_admin_mfa_code_user_id');
        $attempts = $request->session()->get('platform_admin_mfa_attempts', 0);
        $valid = is_string($hash) && is_int($expiresAt) && $expiresAt >= now()->getTimestamp()
            && is_int($attempts) && $attempts < 5 && $challengeUserId === $user->getKey()
            && Hash::check((string) $validated['code'], $hash);
        if (! $valid) {
            $nextAttempts = is_int($attempts) ? $attempts + 1 : 5;
            $request->session()->put('platform_admin_mfa_attempts', $nextAttempts);
            if ($nextAttempts >= 5) {
                $request->session()->forget(['platform_admin_mfa_code_hash', 'platform_admin_mfa_code_expires_at', 'platform_admin_mfa_code_user_id']);
            }
            $this->audit->record(SecurityEvent::AdminMfaFailed, $request, $user);
            throw ValidationException::withMessages(['code' => ['The verification code is invalid or expired.']]);
        }

        $request->session()->forget(['platform_admin_mfa_code_hash', 'platform_admin_mfa_code_expires_at', 'platform_admin_mfa_code_user_id', 'platform_admin_mfa_attempts']);
        $request->session()->put([
            'platform_admin_mfa_verified_at' => now()->getTimestamp(),
            'platform_admin_mfa_user_id' => $user->getKey(),
        ]);
        $this->audit->record(SecurityEvent::AdminMfaVerified, $request, $user);

        return redirect()->intended(route('admin.index'));
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_platform_admin, Response::HTTP_FORBIDDEN);

        return $user;
    }

    private function isVerified(Request $request, User $user): bool
    {
        $verifiedAt = $request->session()->get('platform_admin_mfa_verified_at');

        return is_int($verifiedAt) && $verifiedAt >= now()->subHours(8)->getTimestamp()
            && $request->session()->get('platform_admin_mfa_user_id') === $user->getKey();
    }
}
