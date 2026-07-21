<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->audit->record(SecurityEvent::VerificationSent, $request, $user);
        }

        return response()->json(['meta' => ['message' => 'If verification is required, an email has been sent.']]);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), Response::HTTP_FORBIDDEN);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->tokens()->delete();
            $this->audit->record(SecurityEvent::EmailVerified, $request, $user);
        }

        return response()->json(['meta' => ['message' => 'Email address verified. You may now sign in.']]);
    }
}
