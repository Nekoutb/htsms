<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Integration\WebhookEvent;
use App\Models\DeveloperApiKey;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Integration\WebhookDispatcher;
use App\Services\Integration\WebhookEndpointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_issues_secret_once_and_encrypts_it_at_rest(): void
    {
        [$credential, $key] = $this->apiKey(['webhooks:write', 'webhooks:read']);
        $response = $this->withToken($credential)->postJson('/api/v1/webhook-endpoints', [
            'name' => 'Production events', 'url' => 'https://8.8.8.8/htsms-webhook',
            'events' => ['message.sent', 'message.delivered'],
        ])->assertCreated();
        $secret = $response->json('data.signing_secret');
        self::assertIsString($secret);
        self::assertStringStartsWith('htsms_whsec_', $secret);
        self::assertSame($key->organization_id, WebhookEndpoint::query()->sole()->organization_id);
        self::assertNotSame($secret, WebhookEndpoint::query()->toBase()->value('signing_secret'));

        $this->withToken($credential)->getJson('/api/v1/webhook-endpoints')
            ->assertOk()->assertJsonMissing(['signing_secret' => $secret]);
    }

    public function test_private_and_non_https_webhook_urls_are_rejected(): void
    {
        [$credential] = $this->apiKey(['webhooks:write']);
        foreach (['http://8.8.8.8/hook', 'https://127.0.0.1/hook', 'https://localhost/hook'] as $url) {
            $this->withToken($credential)->postJson('/api/v1/webhook-endpoints', [
                'name' => 'Unsafe endpoint', 'url' => $url, 'events' => ['message.sent'],
            ])->assertUnprocessable();
        }
    }

    public function test_delivery_is_signed_logged_and_contains_stable_event_id(): void
    {
        Http::fake(['https://8.8.8.8/*' => Http::response('', 204)]);
        $organization = Organization::factory()->create();
        $issued = app(WebhookEndpointService::class)->create($organization, 'Signed endpoint', 'https://8.8.8.8/hook', ['message.delivered']);

        app(WebhookDispatcher::class)->dispatch($organization, WebhookEvent::MessageDelivered, ['id' => 'message-1', 'status' => 'delivered']);

        $delivery = WebhookDelivery::query()->sole();
        self::assertSame('delivered', $delivery->status);
        self::assertSame(1, $delivery->attempt_count);
        Http::assertSent(function (Request $request) use ($issued, $delivery): bool {
            $timestamp = $request->header('HTSMS-Timestamp')[0] ?? '';
            $expected = 'v1='.hash_hmac('sha256', $timestamp.'.'.$request->body(), $issued->plainTextSecret);

            return $request->header('HTSMS-Event-ID')[0] === $delivery->event_id
                && hash_equals($expected, $request->header('HTSMS-Signature')[0] ?? '');
        });
    }

    public function test_endpoint_from_another_tenant_is_hidden(): void
    {
        [$credential] = $this->apiKey(['webhooks:write']);
        $other = Organization::factory()->create();
        $endpoint = app(WebhookEndpointService::class)->create($other, 'Other tenant', 'https://8.8.8.8/hook', ['message.sent'])->endpoint;

        $this->withToken($credential)->deleteJson("/api/v1/webhook-endpoints/{$endpoint->id}")->assertNotFound();
    }

    /** @param list<string> $abilities @return array{string, DeveloperApiKey} */
    private function apiKey(array $abilities): array
    {
        $organization = Organization::factory()->create();
        $credential = 'htsms_live_testprefix_'.bin2hex(random_bytes(32));
        $key = $organization->developerApiKeys()->create([
            'created_by_user_id' => User::factory()->create()->getKey(), 'name' => 'Webhook test',
            'prefix' => substr(hash('sha256', $credential), 0, 12), 'secret_hash' => hash('sha256', $credential), 'abilities' => $abilities,
        ]);

        return [$credential, $key];
    }
}
