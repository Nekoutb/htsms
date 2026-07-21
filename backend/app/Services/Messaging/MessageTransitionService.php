<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Domain\Integration\WebhookEvent;
use App\Domain\Messaging\MessageStatus;
use App\Models\Message;
use App\Services\Integration\WebhookDispatcher;
use DomainException;

final class MessageTransitionService
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    /** @param array<string, bool|int|string|null> $metadata */
    public function transition(Message $message, MessageStatus $next, string $source, array $metadata = []): Message
    {
        $current = $message->status;
        if (! $current->canTransitionTo($next)) {
            throw new DomainException("Invalid message transition from {$current->value} to {$next->value}.");
        }

        $attributes = ['status' => $next];
        if ($next === MessageStatus::Assigned) {
            $attributes['assigned_at'] = now();
            $attributes['attempt_count'] = $message->attempt_count + 1;
        } elseif ($next === MessageStatus::Sent) {
            $attributes['sent_at'] = now();
        } elseif ($next === MessageStatus::Delivered) {
            $attributes['delivered_at'] = now();
        }

        $message->forceFill($attributes)->save();
        $message->events()->create([
            'from_status' => $current->value,
            'to_status' => $next->value,
            'source' => $source,
            'metadata' => $metadata,
        ]);

        $event = match ($next) {
            MessageStatus::Sent => WebhookEvent::MessageSent,
            MessageStatus::Delivered => WebhookEvent::MessageDelivered,
            MessageStatus::Failed => WebhookEvent::MessageFailed,
            MessageStatus::Expired => WebhookEvent::MessageExpired,
            default => null,
        };
        if ($event !== null) {
            $organization = $message->organization()->firstOrFail();
            $this->webhooks->dispatch($organization, $event, [
                'id' => $message->id, 'to' => $message->recipient, 'content' => $message->body,
                'status' => $message->status->value, 'failure_code' => $message->failure_code,
            ]);
        }

        return $message->refresh();
    }
}
