<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeveloperApiKeyRequest;
use App\Http\Resources\Api\V1\DeveloperApiKeyResource;
use App\Models\DeveloperApiKey;
use App\Models\Organization;
use App\Models\User;
use App\Services\Integration\DeveloperApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class DeveloperApiKeyController extends Controller
{
    public function __construct(
        private readonly DeveloperApiKeyService $apiKeys,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('manageApiKeys', $organization);

        return DeveloperApiKeyResource::collection(
            $organization->developerApiKeys()->latest()->paginate(20),
        );
    }

    public function store(StoreDeveloperApiKeyRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageApiKeys', $organization);
        /** @var User $user */
        $user = $request->user();
        $issued = $this->apiKeys->issue($organization, $user, $request->toData());

        return response()->json([
            'data' => [
                ...(new DeveloperApiKeyResource($issued->apiKey))->resolve($request),
                'plain_text_key' => $issued->plainTextKey,
            ],
            'meta' => [
                'message' => 'Copy this key now. It will not be shown again.',
            ],
        ], Response::HTTP_CREATED);
    }

    public function revoke(Request $request, Organization $organization, DeveloperApiKey $apiKey): JsonResponse
    {
        $this->authorize('manageApiKeys', $organization);
        $this->ensureBelongsToOrganization($apiKey, $organization);
        $this->apiKeys->revoke($apiKey);

        return response()->json(['meta' => ['message' => 'API key revoked.']]);
    }

    public function rotate(Request $request, Organization $organization, DeveloperApiKey $apiKey): JsonResponse
    {
        $this->authorize('manageApiKeys', $organization);
        $this->ensureBelongsToOrganization($apiKey, $organization);
        /** @var User $user */
        $user = $request->user();
        $issued = $this->apiKeys->rotate($apiKey, $user);

        return response()->json([
            'data' => [
                ...(new DeveloperApiKeyResource($issued->apiKey))->resolve($request),
                'plain_text_key' => $issued->plainTextKey,
            ],
            'meta' => ['message' => 'Previous key revoked. Copy the replacement now.'],
        ], Response::HTTP_CREATED);
    }

    private function ensureBelongsToOrganization(DeveloperApiKey $apiKey, Organization $organization): void
    {
        abort_unless($apiKey->organization_id === $organization->getKey(), Response::HTTP_NOT_FOUND);
    }
}
