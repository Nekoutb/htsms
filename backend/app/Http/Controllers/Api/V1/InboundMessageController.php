<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Integration\WebhookEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInboundMessageRequest;
use App\Http\Resources\Api\V1\InboundMessageResource;
use App\Models\Device;
use App\Models\InboundMessage;
use App\Models\Organization;
use App\Services\Integration\WebhookDispatcher;
use App\Services\Marketing\InboundOptOutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InboundMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);

        return InboundMessageResource::collection($organization->inboundMessages()->latest('received_at')->paginate(50))->response();
    }

    public function store(StoreInboundMessageRequest $request, WebhookDispatcher $webhooks, InboundOptOutService $optOuts): JsonResponse
    {
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device, Response::HTTP_UNAUTHORIZED);
        $organization = Organization::query()->findOrFail($device->organization_id);
        abort_unless($organization->inbound_enabled, Response::HTTP_FORBIDDEN, 'Inbound messaging is disabled for this workspace.');
        $existing = $organization->inboundMessages()->where('device_id', $device->id)
            ->where('device_event_id', $request->string('device_event_id')->toString())->first();
        if ($existing !== null) {
            $optOuts->process($existing);

            return (new InboundMessageResource($existing))->response();
        }
        $sim = $device->simSlots()->where('slot_index', $request->integer('sim_slot_index'))->first();
        $message = InboundMessage::query()->create([
            'organization_id' => $device->organization_id, 'device_id' => $device->id,
            'device_sim_slot_id' => $sim?->getKey(), 'device_event_id' => $request->string('device_event_id')->toString(),
            'sender' => $request->string('sender')->toString(), 'recipient' => $request->input('recipient'),
            'body' => $request->string('content')->toString(), 'received_at' => $request->date('received_at'),
        ]);
        $optOuts->process($message);
        $webhooks->dispatch($organization, WebhookEvent::MessageReceived, (new InboundMessageResource($message))->resolve($request));

        return (new InboundMessageResource($message))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
