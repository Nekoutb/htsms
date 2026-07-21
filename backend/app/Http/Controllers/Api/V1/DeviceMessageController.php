<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Messaging\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMessageStatusRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Device;
use App\Models\Message;
use App\Services\Messaging\MessageTransitionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class DeviceMessageController extends Controller
{
    public function lease(Request $request, MessageTransitionService $transitions): JsonResponse
    {
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device, Response::HTTP_UNAUTHORIZED);

        $message = DB::transaction(function () use ($device, $transitions): ?Message {
            $candidate = Message::query()
                ->where('organization_id', $device->organization_id)
                ->where('status', MessageStatus::Queued)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->lockForUpdate()
                ->oldest()
                ->first();
            if ($candidate === null) {
                return null;
            }
            $candidate->forceFill(['device_id' => $device->id])->save();

            return $transitions->transition($candidate, MessageStatus::Assigned, 'device');
        });

        return $message === null
            ? response()->json(null, Response::HTTP_NO_CONTENT)
            : (new MessageResource($message))->response();
    }

    public function update(UpdateMessageStatusRequest $request, Message $message, MessageTransitionService $transitions): JsonResponse
    {
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device && $message->device_id === $device->id, Response::HTTP_NOT_FOUND);
        $next = MessageStatus::from($request->string('status')->toString());

        try {
            if ($next === MessageStatus::Failed) {
                $message->forceFill([
                    'failure_code' => $request->string('failure_code')->toString(),
                    'failure_message' => $request->input('failure_message'),
                ])->save();
            }
            $message = $transitions->transition($message, $next, 'device');
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return (new MessageResource($message))->response();
    }
}
