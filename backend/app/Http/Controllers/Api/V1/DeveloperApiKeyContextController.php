<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApiKey;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeveloperApiKeyContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('developer_api_key');
        $organization = $request->attributes->get('organization');
        abort_unless($apiKey instanceof DeveloperApiKey && $organization instanceof Organization, 500);

        return response()->json([
            'data' => [
                'organization_id' => $organization->getKey(),
                'api_key_id' => $apiKey->getKey(),
                'api_key_name' => $apiKey->name,
                'abilities' => $apiKey->abilities,
            ],
        ]);
    }
}
