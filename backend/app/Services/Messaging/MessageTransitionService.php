<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Domain\Messaging\MessageStatus;
use App\Models\Message;
use DomainException;

final class MessageTransitionService
{
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

        return $message->refresh();
    }
}
