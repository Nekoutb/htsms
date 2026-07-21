<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityHeadersAndHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_responses_include_security_headers(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_readiness_checks_database_and_cache(): void
    {
        $this->getJson('/health/ready')->assertOk()->assertExactJson(['status' => 'ready']);
    }
}
