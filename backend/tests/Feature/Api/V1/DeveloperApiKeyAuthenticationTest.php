<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\DeveloperApiKey;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeveloperApiKeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_key_authenticates_into_its_organization_context(): void
    {
        [$plainText, $apiKey] = $this->apiKey(['messages:read']);

        $this->withToken($plainText)
            ->getJson('/api/v1/api-key')
            ->assertOk()
            ->assertJsonPath('data.organization_id', $apiKey->organization_id)
            ->assertJsonPath('data.api_key_id', $apiKey->getKey());

        self::assertNotNull($apiKey->fresh()?->last_used_at);
    }

    public function test_missing_and_unknown_keys_return_same_generic_error(): void
    {
        $this->getJson('/api/v1/api-key')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Invalid or expired API key.']);

        $this->withToken('htsms_live_unknown_'.str_repeat('0', 64))
            ->getJson('/api/v1/api-key')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Invalid or expired API key.']);
    }

    public function test_revoked_and_expired_keys_are_rejected(): void
    {
        [$revokedPlainText, $revoked] = $this->apiKey(['messages:read']);
        $revoked->forceFill(['revoked_at' => now()])->save();

        $this->withToken($revokedPlainText)->getJson('/api/v1/api-key')->assertUnauthorized();

        [$expiredPlainText, $expired] = $this->apiKey(['messages:read']);
        $expired->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withToken($expiredPlainText)->getJson('/api/v1/api-key')->assertUnauthorized();
    }

    public function test_ability_middleware_rejects_insufficient_key(): void
    {
        [$plainText] = $this->apiKey(['messages:write']);

        $this->withToken($plainText)
            ->getJson('/api/v1/api-key')
            ->assertForbidden()
            ->assertExactJson(['message' => 'API key does not have the required ability.']);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{string, DeveloperApiKey}
     */
    private function apiKey(array $abilities): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $plainText = 'htsms_live_testprefix_'.bin2hex(random_bytes(32));
        $apiKey = $organization->developerApiKeys()->create([
            'created_by_user_id' => $user->getKey(),
            'name' => 'Test key',
            'prefix' => substr(hash('sha256', $plainText), 0, 12),
            'secret_hash' => hash('sha256', $plainText),
            'abilities' => $abilities,
        ]);

        return [$plainText, $apiKey];
    }
}
