<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('contact@cm-ea.com')
            ->assertSee('mailto:contact@cm-ea.com', false);
    }

    public function test_mailersend_api_transport_is_available(): void
    {
        $this->assertSame('mailersend', config('mail.mailers.mailersend.transport'));
    }
}
