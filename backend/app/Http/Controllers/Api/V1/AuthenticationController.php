<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Identity\AuthenticationService;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly SecurityAuditService $audit,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authentication->register($request->toData());
        $verificationToken = $user->createToken('email-verification', ['email:verify'], now()->addDay());
        $user->sendEmailVerificationNotification();
        $this->audit->record(SecurityEvent::Registered, $request, $user);
        $this->audit->record(SecurityEvent::VerificationSent, $request, $user);

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve($request),
                'verification_token' => $verificationToken->plainTextToken,
            ],
            'meta' => ['message' => 'Account created. Verify your email before signing in.'],
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $issued = $this->authentication->authenticate($request->toData());
        } catch (AuthenticationException) {
            $this->audit->record(SecurityEvent::LoginFailed, $request, metadata: [
                'email_fingerprint' => $this->audit->emailFingerprint($request->string('email')->toString()),
            ]);

            throw ValidationException::withMessages([
                'email' => ['The supplied credentials are invalid.'],
            ]);
        }

        $this->audit->record(SecurityEvent::LoginSucceeded, $request, $issued->user);

        return response()->json([
            'data' => [
                'user' => (new UserResource($issued->user))->resolve($request),
                'token' => $issued->plainTextToken,
            ],
        ]);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $this->audit->record(SecurityEvent::LoggedOut, $request, $user);

        return response()->json(['meta' => ['message' => 'Signed out.']]);
    }
}
