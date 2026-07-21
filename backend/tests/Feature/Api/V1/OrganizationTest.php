<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_list_organizations(): void
    {
        $this->getJson('/api/v1/organizations')->assertUnauthorized();
    }

    public function test_user_can_create_organization_and_becomes_owner(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['organizations:write']);

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'CM-EA Operations',
            'slug' => 'cm-ea-operations',
            'timezone' => 'Africa/Douala',
            'locale' => 'fr',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'cm-ea-operations');

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $response->json('data.id'),
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_user_only_lists_organizations_they_belong_to(): void
    {
        $user = User::factory()->create();
        $own = Organization::factory()->create(['name' => 'Own Organization']);
        $other = Organization::factory()->create(['name' => 'Other Organization']);
        $own->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Developer,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user, ['organizations:read']);

        $response = $this->getJson('/api/v1/organizations')->assertOk();

        $response->assertJsonFragment(['id' => $own->getKey()]);
        $response->assertJsonMissing(['id' => $other->getKey()]);
    }

    public function test_token_without_required_ability_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['messages:write']);

        $this->getJson('/api/v1/organizations')->assertForbidden();
    }
}
