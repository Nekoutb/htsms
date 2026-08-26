<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Identity\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Front-end hygiene the web audit fixed: every page must carry a real title,
 * a description, working icons and a reachable navigation on a phone.
 */
final class SitePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_has_title_description_and_icons(): void
    {
        foreach (['/', '/login', '/register'] as $path) {
            $response = $this->get($path)->assertOk();
            $html = $response->getContent();

            self::assertIsString($html);
            self::assertMatchesRegularExpression('/<title>[^<]{3,}<\/title>/', $html, $path.' has no page title');
            self::assertDoesNotMatchRegularExpression('/<title>\s*·/', $html, $path.' renders an empty title before the separator');
            $response->assertSee('name="description"', false)
                ->assertSee('rel="icon"', false)
                ->assertSee('rel="apple-touch-icon"', false);
        }
    }

    public function test_unknown_url_renders_the_branded_error_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee(config('app.support_email'))
            ->assertSee('rel="icon"', false)
            ->assertDontSee('Whoops, looks like something went wrong');
    }

    public function test_marketing_header_offers_a_mobile_menu_control(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-nav-toggle', false)
            ->assertSee('id="site-nav"', false)
            ->assertSee('data-nav-close', false);
    }

    public function test_portal_keeps_navigation_and_sign_out_reachable_on_mobile(): void
    {
        [$user, $organization] = $this->membership();

        $this->actingAs($user)->get(route('portal.overview', $organization))->assertOk()
            ->assertSee('portal-topbar', false)
            ->assertSee('data-nav-toggle', false)
            ->assertSee('id="portal-sidebar"', false);
    }

    public function test_footer_exposes_clickable_contacts_and_the_current_year(): void
    {
        config()->set('app.support_phone', '+237 670 00 00 00');

        $this->get('/')->assertOk()
            ->assertSee('mailto:'.config('app.support_email'), false)
            ->assertSee('href="tel:+237670000000"', false)
            ->assertSee('© '.date('Y').' Elite Advisors', false);
    }

    public function test_footer_omits_the_phone_link_when_no_number_is_configured(): void
    {
        config()->set('app.support_phone', null);

        $this->get('/')->assertOk()->assertDontSee('href="tel:', false);
    }

    public function test_favicon_and_touch_icon_are_served_and_not_empty(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'brand/apple-touch-icon.png', 'brand/ea-mark.svg'] as $asset) {
            $path = public_path($asset);
            self::assertFileExists($path);
            self::assertGreaterThan(100, filesize($path) ?: 0, $asset.' is empty');
        }
    }

    public function test_robots_disallows_private_areas_and_sitemap_lists_public_pages(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        self::assertIsString($robots);
        self::assertStringContainsString('Disallow: /admin', $robots);
        self::assertStringContainsString('Disallow: /app/', $robots);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('home'), false)
            ->assertSee(route('login'), false);
    }

    public function test_legal_pages_render_with_canonical_and_appear_in_sitemap(): void
    {
        foreach (['/terms' => 'Terms of Service', '/privacy' => 'Privacy Policy'] as $path => $heading) {
            $this->get($path)->assertOk()
                ->assertSee($heading)
                ->assertSee('rel="canonical"', false)
                ->assertSee(config('app.support_email'));
        }

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(route('terms'), false)
            ->assertSee(route('privacy'), false);
    }

    public function test_footer_links_to_terms_and_privacy(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('terms'), false)
            ->assertSee(route('privacy'), false);
    }

    public function test_home_exposes_structured_data_and_a_social_image(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('brand/og-image.svg', false);
    }

    public function test_llms_txt_is_present_and_describes_the_site(): void
    {
        $path = public_path('llms.txt');
        self::assertFileExists($path);
        $contents = (string) file_get_contents($path);
        self::assertStringContainsString('EA HTSMS', $contents);
        self::assertStringContainsString('htsms.cm-ea.com', $contents);
    }

    public function test_turnstile_blocks_auth_when_configured_but_unsolved(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');
        config()->set('services.turnstile.site_key', 'test-site-key');

        $this->post('/register', [
            'name' => 'Bot Test',
            'email' => 'bot@example.com',
        ])->assertSessionHasErrors('captcha');

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_turnstile_is_skipped_when_not_configured(): void
    {
        config()->set('services.turnstile.secret', '');

        $this->post('/register', [
            'name' => 'Real User',
            'email' => 'real@example.com',
        ])->assertSessionDoesntHaveErrors('captcha');

        $this->assertDatabaseHas('users', ['email' => 'real@example.com']);
    }

    /** @return array{0: User, 1: Organization} */
    private function membership(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = Organization::factory()->create();
        $organization->memberships()->create([
            'user_id' => $user->getKey(),
            'role' => OrganizationRole::Owner,
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }
}
