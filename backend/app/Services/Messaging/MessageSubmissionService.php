<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Domain\Messaging\MessageStatus;
use App\DTO\Messaging\SubmittedMessage;
use App\Models\Message;
use App\Models\Organization;
use App\Services\Billing\SubscriptionService;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final readonly class MessageSubmissionService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function submit(Organization $organization, string $recipient, string $body, ?DateTimeInterface $scheduledAt, ?DateTimeInterface $expiresAt, ?string $idempotencyKey, string $source, ?int $preferredSimSlot = null): SubmittedMessage
    {
        return DB::transaction(function () use ($organization, $recipient, $body, $scheduledAt, $expiresAt, $idempotencyKey, $source, $preferredSimSlot): SubmittedMessage {
            if ($idempotencyKey !== null) {
                $existing = Message::query()->whereBelongsTo($organization)->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return new SubmittedMessage($existing, false);
                }
            }
            $this->subscriptions->consumeMessage($organization);
            $message = $organization->messages()->create([
                'recipient' => $recipient, 'body' => $body,
                'status' => $scheduledAt === null ? MessageStatus::Queued : MessageStatus::Scheduled,
                'idempotency_key' => $idempotencyKey, 'scheduled_at' => $scheduledAt, 'expires_at' => $expiresAt,
                'preferred_sim_slot' => $preferredSimSlot,
            ]);
            $message->events()->create(['to_status' => $message->status->value, 'source' => $source]);

            return new SubmittedMessage($message, true);
        });
    }
}
