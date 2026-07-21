<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Messaging\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);
        $messages = Message::query()->whereBelongsTo($organization)->latest()->paginate(50);

        return MessageResource::collection($messages)->response();
    }

    public function store(StoreMessageRequest $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_if(is_string($idempotencyKey) && strlen($idempotencyKey) > 100, Response::HTTP_UNPROCESSABLE_ENTITY, 'Idempotency-Key is too long.');

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $existing = Message::query()->whereBelongsTo($organization)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return (new MessageResource($existing))->response();
            }
        }

        $scheduledAt = $request->date('send_at');
        $message = Message::query()->create([
            'organization_id' => $organization->getKey(),
            'recipient' => $request->string('to')->toString(),
            'body' => $request->string('content')->toString(),
            'status' => $scheduledAt === null ? MessageStatus::Queued : MessageStatus::Scheduled,
            'idempotency_key' => is_string($idempotencyKey) && $idempotencyKey !== '' ? $idempotencyKey : null,
            'scheduled_at' => $scheduledAt,
            'expires_at' => $request->date('expires_at'),
        ]);
        $message->events()->create(['to_status' => $message->status->value, 'source' => 'api']);

        return (new MessageResource($message))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
