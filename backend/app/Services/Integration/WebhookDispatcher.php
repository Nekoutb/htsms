<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Domain\Integration\WebhookEvent;
use App\Jobs\DeliverWebhook;
use App\Models\Organization;
use Illuminate\Support\Str;

final class WebhookDispatcher
{
    /** @param array<string, mixed> $data */
    public function dispatch(Organization $organization, WebhookEvent $event, array $data): void
    {
        $eventId = (string) Str::uuid();
        $payload = ['id' => $eventId, 'type' => $event->value, 'created_at' => now()->toIso8601String(), 'data' => $data];
        $endpoints = $organization->webhookEndpoints()->where('is_active', true)->get()
            ->filter(fn ($endpoint): bool => in_array($event->value, $endpoint->events, true));
        foreach ($endpoints as $endpoint) {
            $delivery = $endpoint->deliveries()->create([
                'event_id' => $eventId, 'event_type' => $event->value, 'payload' => $payload, 'status' => 'pending',
            ]);
            DeliverWebhook::dispatch($delivery->id)->afterCommit();
        }
    }
}
