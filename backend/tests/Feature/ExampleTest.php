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
            ->assertSee(config('htsms.apk.path'), false);
    }

    public function test_mailersend_api_transport_is_available(): void
    {
        $this->assertSame('mailersend', config('mail.mailers.mailersend.transport'));
    }

    public function test_signed_android_apk_and_checksum_are_published(): void
    {
        $apk = public_path(config('htsms.apk.path'));
        $checksum = public_path(config('htsms.apk.checksum_path'));

        $this->assertFileExists($apk);
        $this->assertFileExists($checksum);
        // The published checksum file must match the published binary.
        $this->assertSame(
            explode(' ', trim((string) file_get_contents($checksum)))[0],
            hash_file('sha256', $apk),
        );
    }
}
