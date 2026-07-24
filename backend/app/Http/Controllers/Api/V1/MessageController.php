<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use App\Models\Organization;
use App\Services\Messaging\MessageSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class MessageController extends Controller
{
    public function __construct(private readonly MessageSubmissionService $submissions) {}

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

        $scheduledAt = $request->date('send_at');
        $submitted = $this->submissions->submit(
            $organization, $request->string('to')->toString(), $request->string('content')->toString(),
            $scheduledAt, $request->date('expires_at'),
            is_string($idempotencyKey) && $idempotencyKey !== '' ? $idempotencyKey : null, 'api',
            $request->has('sim_slot') ? $request->integer('sim_slot') : null,
        );

        return (new MessageResource($submitted->message))->response()
            ->setStatusCode($submitted->created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
