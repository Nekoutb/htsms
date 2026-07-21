<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookEndpointRequest;
use App\Http\Resources\Api\V1\WebhookEndpointResource;
use App\Jobs\DeliverWebhook;
use App\Models\Organization;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Integration\WebhookEndpointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookEndpointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        return WebhookEndpointResource::collection($organization->webhookEndpoints()->latest()->paginate(25))->response();
    }

    public function store(StoreWebhookEndpointRequest $request, WebhookEndpointService $service): JsonResponse
    {
        $issued = $service->create(
            $this->organization($request), $request->string('name')->toString(),
            $request->string('url')->toString(), $request->eventValues(),
        );

        return response()->json(['data' => [
            ...(new WebhookEndpointResource($issued->endpoint))->resolve($request),
            'signing_secret' => $issued->plainTextSecret,
        ], 'meta' => ['message' => 'Copy the signing secret now. It will not be shown again.']], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint, WebhookEndpointService $service): JsonResponse
    {
        $this->ensureTenant($request, $webhookEndpoint);
        $service->disable($webhookEndpoint);

        return response()->json(['meta' => ['message' => 'Webhook endpoint disabled.']]);
    }

    public function deliveries(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->ensureTenant($request, $webhookEndpoint);

        return response()->json(['data' => $webhookEndpoint->deliveries()->latest()->paginate(50)]);
    }

    public function replay(Request $request, WebhookEndpoint $webhookEndpoint, WebhookDelivery $delivery): JsonResponse
    {
        $this->ensureTenant($request, $webhookEndpoint);
        abort_unless($delivery->webhook_endpoint_id === $webhookEndpoint->id, Response::HTTP_NOT_FOUND);
        $delivery->forceFill(['status' => 'pending', 'response_status' => null, 'response_excerpt' => null])->save();
        DeliverWebhook::dispatch($delivery->id);

        return response()->json(['meta' => ['message' => 'Webhook delivery queued for replay.']], Response::HTTP_ACCEPTED);
    }

    private function organization(Request $request): Organization
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);

        return $organization;
    }

    private function ensureTenant(Request $request, WebhookEndpoint $endpoint): void
    {
        abort_unless($endpoint->organization_id === $this->organization($request)->id, Response::HTTP_NOT_FOUND);
    }
}
