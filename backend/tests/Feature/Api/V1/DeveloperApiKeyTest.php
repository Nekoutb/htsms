<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\OrganizationRole;
use App\Models\DeveloperApiKey;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DeveloperApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_issue_api_key_and_only_hash_is_stored(): void
    {
        [$user, $organization] = $this->member(OrganizationRole::Owner);
        Sanctum::actingAs($user, ['api-keys:write']);

        $response = $this->postJson("/api/v1/organizations/{$organization->getKey()}/api-keys", [
            'name' => 'Production integration',
            'abilities' => ['messages:read', 'messages:write'],
        ])->assertCreated();

        $plainTextKey = $response->json('data.plain_text_key');
        self::assertIsString($plainTextKey);
        self::assertStringStartsWith('htsms_live_', $plainTextKey);

        $apiKey = DeveloperApiKey::query()->sole();
        self::assertSame(hash('sha256', $plainTextKey), $apiKey->secret_hash);
        self::assertNotSame($plainTextKey, $apiKey->secret_hash);
    }

    public function test_list_never_returns_plain_text_key_or_hash(): void
    {
        [$user, $organization] = $this->member(OrganizationRole::Owner);
        $organization->developerApiKeys()->create([
            'created_by_user_id' => $user->getKey(),
            'name' => 'Existing',
            'prefix' => 'abcdefghijkl',
            'secret_hash' => str_repeat('a', 64),
            'abilities' => ['messages:read'],
        ]);
        Sanctum::actingAs($user, ['api-keys:read']);

        $content = $this->getJson("/api/v1/organizations/{$organization->getKey()}/api-keys")
            ->assertOk()
            ->getContent();

        self::assertStringNotContainsString('secret_hash', $content);
        self::assertStringNotContainsString('plain_text_key', $content);
        self::assertStringNotContainsString(str_repeat('a', 64), $content);
    }

    public function test_non_administrative_member_cannot_manage_api_keys(): void
    {
        [$user, $organization] = $this->member(OrganizationRole::Developer);
        Sanctum::actingAs($user, ['api-keys:write']);

        $this->postJson("/api/v1/organizations/{$organization->getKey()}/api-keys", [
            'name' => 'Forbidden key',
            'abilities' => ['messages:write'],
        ])->assertForbidden();
    }

    public function test_api_key_from_another_organization_cannot_be_revoked(): void
    {
        [$user, $organization] = $this->member(OrganizationRole::Owner);
        $other = Organization::factory()->create();
        $otherKey = $other->developerApiKeys()->create([
            'created_by_user_id' => $user->getKey(),
            'name' => 'Other organization',
            'prefix' => 'zyxwvutsrqpo',
            'secret_hash' => str_repeat('b', 64),
            'abilities' => ['messages:write'],
        ]);
        Sanctum::actingAs($user, ['api-keys:write']);

        $this->deleteJson("/api/v1/organizations/{$organization->getKey()}/api-keys/{$otherKey->getKey()}")
            ->assertNotFound();

        self::assertNull($otherKey->fresh()?->revoked_at);
    }

    public function test_rotation_revokes_old_key_and_returns_different_replacement(): void
    {
        [$user, $organization] = $this->member(OrganizationRole::Owner);
        $old = $organization->developerApiKeys()->create([
            'created_by_user_id' => $user->getKey(),
            'name' => 'Rotate me',
            'prefix' => 'oldkeyprefix',
            'secret_hash' => str_repeat('c', 64),
            'abilities' => ['messages:write'],
        ]);
        Sanctum::actingAs($user, ['api-keys:write']);

        $response = $this->postJson("/api/v1/organizations/{$organization->getKey()}/api-keys/{$old->getKey()}/rotate")
            ->assertCreated();

        self::assertNotNull($old->fresh()?->revoked_at);
        self::assertNotSame($old->getKey(), $response->json('data.id'));
        self::assertStringStartsWith('htsms_live_', $response->json('data.plain_text_key'));
    }

    /**
     * @return array{User, Organization}
     */
    private function member(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => $role,
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }
}
