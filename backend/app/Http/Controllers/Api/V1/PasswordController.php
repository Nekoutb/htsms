<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Models\User;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->string('email')->trim()->lower()->toString();
        Password::sendResetLink(['email' => $email]);
        $this->audit->record(SecurityEvent::PasswordResetRequested, $request, metadata: [
            'email_fingerprint' => $this->audit->emailFingerprint($email),
        ]);

        return response()->json([
            'meta' => ['message' => 'If the account exists, a password reset email has been sent.'],
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
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
            throw ValidationException::withMessages(['email' => ['The reset token is invalid or expired.']]);
        }

        return response()->json(['meta' => ['message' => 'Password reset. Sign in with the new password.']]);
    }
}
