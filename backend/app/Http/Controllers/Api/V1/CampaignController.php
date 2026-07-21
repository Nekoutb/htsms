<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCampaignRequest;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\Campaign;
use App\Models\Organization;
use App\Services\Marketing\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns) {}

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);

        return CampaignResource::collection(Campaign::query()->whereBelongsTo($organization)->latest()->paginate(50))->response();
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);
        $contactIds = [];
        foreach ($request->array('contact_ids') as $contactId) {
            if (is_string($contactId)) {
                $contactIds[] = $contactId;
            }
        }
        $campaign = $this->campaigns->create(
            $organization,
            $request->string('name')->toString(),
            $request->string('content')->toString(),
            $contactIds,
            $request->date('send_at'),
        );

        return (new CampaignResource($campaign))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
