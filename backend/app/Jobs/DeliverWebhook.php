<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Rules\PublicHttpsUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class DeliverWebhook implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 1800, 7200];

    public function __construct(public readonly string $deliveryId)
    {
        $this->onQueue('webhooks');
    }

    public function handle(PublicHttpsUrl $urlRule): void
    {
        $delivery = WebhookDelivery::query()->findOrFail($this->deliveryId);
        $endpoint = $delivery->endpoint()->firstOrFail();
        if (! $endpoint->is_active || ! $urlRule->hostIsPublic((string) parse_url($endpoint->url, PHP_URL_HOST))) {
            throw new RuntimeException('Webhook endpoint is disabled or no longer resolves publicly.');
        }
        $json = json_encode($delivery->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->getTimestamp();
        $signature = hash_hmac('sha256', $timestamp.'.'.$json, $endpoint->signing_secret);
        $response = Http::connectTimeout(5)->timeout(10)->withHeaders([
            'Content-Type' => 'application/json', 'User-Agent' => 'HTSMS-Webhooks/1.0',
            'HTSMS-Event-ID' => $delivery->event_id, 'HTSMS-Event-Type' => $delivery->event_type,
            'HTSMS-Timestamp' => $timestamp, 'HTSMS-Signature' => 'v1='.$signature,
        ])->withBody($json, 'application/json')->post($endpoint->url);
        $delivery->forceFill([
            'attempt_count' => $delivery->attempt_count + 1,
            'last_attempt_at' => now(), 'response_status' => $response->status(),
            'response_excerpt' => mb_substr($response->body(), 0, 1000),
            'status' => $response->successful() ? 'delivered' : 'retrying',
            'delivered_at' => $response->successful() ? now() : null,
        ])->save();
        if ($response->successful()) {
            $endpoint->forceFill(['consecutive_failures' => 0])->save();

            return;
        }
        throw new RuntimeException('Webhook returned HTTP '.$response->status().'.');
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);
        if ($delivery === null) {
            return;
        }
        $delivery->forceFill(['status' => 'failed'])->save();
        $endpoint = $delivery->endpoint()->firstOrFail();
        $failures = $endpoint->consecutive_failures + 1;
        $endpoint->forceFill([
            'consecutive_failures' => $failures,
            'is_active' => $failures < 10,
            'disabled_at' => $failures >= 10 ? now() : null,
        ])->save();
    }
}
