<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\User;
use App\Services\Identity\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizations,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return OrganizationResource::collection(
            $user->organizations()->orderBy('name')->paginate(20),
        );
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $organization = $this->organizations->createForOwner($request->toData(), $user);

        return (new OrganizationResource($organization))
            ->additional(['meta' => ['message' => 'Organization created.']])
            ->response()
            ->setStatusCode(201);
    }
}
