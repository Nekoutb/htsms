<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Identity\AuthenticationService;
use App\Services\Identity\MagicLinkService;
use App\Services\Identity\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly MagicLinkService $magicLinks,
        private readonly SecurityAuditService $audit,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authentication->register($request->toData());
        $this->magicLinks->sendTo($user->email, $request);
        $this->audit->record(SecurityEvent::Registered, $request, $user);
        $this->audit->record(SecurityEvent::MagicLinkRequested, $request, $user);

        return response()->json([
            'data' => ['user' => (new UserResource($user))->resolve($request)],
            'meta' => ['message' => 'Account created. Check your email for a sign-in link.'],
        ], Response::HTTP_CREATED);
    }

    /**
     * Email a single-use sign-in link. Responds identically whether or not an
     * account exists so it never reveals which emails are registered.
     */
    public function requestLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email:rfc', 'max:254']]);
        $email = mb_strtolower(trim($request->string('email')->toString()));

        $this->magicLinks->sendTo($email, $request);
        $this->audit->record(SecurityEvent::MagicLinkRequested, $request, metadata: [
            'email_fingerprint' => $this->audit->emailFingerprint($email),
        ]);

        return response()->json([
            'meta' => ['message' => 'If an account exists for that email, a sign-in link is on its way.'],
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
