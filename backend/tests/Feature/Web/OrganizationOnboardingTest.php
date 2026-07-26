<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Device;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_shows_the_single_field_workspace_step(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/app')
            ->assertOk()
            ->assertSee('Name your workspace')
            ->assertSee('Optional settings');
    }

    public function test_workspace_is_created_from_the_name_alone_with_a_unique_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/organizations', ['name' => 'Acme Cameroon'])
            ->assertRedirect();

        $organization = Organization::query()->where('name', 'Acme Cameroon')->sole();
        self::assertSame('acme-cameroon', $organization->slug);
        self::assertSame('Africa/Douala', $organization->timezone);
        self::assertSame('en', $organization->locale);
        self::assertNotNull($organization->subscription()->first());

        $second = User::factory()->create();
        $this->actingAs($second)->post('/app/organizations', ['name' => 'Acme Cameroon'])
            ->assertRedirect();
        $slugs = Organization::query()->pluck('slug');
        self::assertCount(2, $slugs->unique());
        self::assertMatchesRegularExpression('/^acme-cameroon-[a-z0-9]{4}$/', (string) $slugs->last());
    }

    public function test_custom_slug_and_timezone_are_still_honoured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/organizations', [
            'name' => 'Acme Cameroon',
            'slug' => 'acme-hq',
            'timezone' => 'Africa/Nairobi',
            'locale' => 'fr',
        ])->assertRedirect();

        $organization = Organization::query()->sole();
        self::assertSame('acme-hq', $organization->slug);
        self::assertSame('Africa/Nairobi', $organization->timezone);
        self::assertSame('fr', $organization->locale);
    }

    public function test_overview_quickstart_tracks_real_progress(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Owner,
            'joined_at' => now(),
        ]);

        $this->actingAs($user)->get("/app/{$organization->getKey()}")
            ->assertOk()
            ->assertSee('Connect a SIM gateway')
            ->assertSee('Authenticate your app')
            ->assertSee('Test end-to-end delivery');

        Device::factory()->for($organization)->create(['last_seen_at' => now()->subHour()]);
        $this->actingAs($user)->post("/app/{$organization->getKey()}/developer/api-keys", [
            'name' => 'Production backend',
            'abilities' => ['messages:read'],
        ])->assertRedirect();
        $this->actingAs($user)->post("/app/{$organization->getKey()}/messages", [
            'to' => '+237670000002',
            'content' => 'Quickstart end-to-end test.',
        ])->assertRedirect();

        $this->actingAs($user)->get("/app/{$organization->getKey()}")
            ->assertOk()
            ->assertSee('Paired · currently offline')
            ->assertSee('Key active')
            ->assertSee('Delivery flow working');
    }
}
