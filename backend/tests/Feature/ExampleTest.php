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
            ->assertSee('mailto:contact@cm-ea.com', false)
            ->assertSee('downloads/htsms-gateway-v0.1.0-beta.apk', false);
    }

    public function test_mailersend_api_transport_is_available(): void
    {
        $this->assertSame('mailersend', config('mail.mailers.mailersend.transport'));
    }

    public function test_signed_android_beta_and_checksum_are_published(): void
    {
        $apk = public_path('downloads/htsms-gateway-v0.1.0-beta.apk');

        $this->assertFileExists($apk);
        $this->assertSame(
            'd0c48aa356bcbea12be09423a0a1732f939882438d0c3d441b76f215621ad419',
            hash_file('sha256', $apk),
        );
    }
}
