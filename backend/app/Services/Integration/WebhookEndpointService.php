<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\DTO\Integration\IssuedWebhookSecret;
use App\Models\Organization;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;

final class WebhookEndpointService
{
    /** @param list<string> $events */
    public function create(Organization $organization, string $name, string $url, array $events): IssuedWebhookSecret
    {
        $prefix = Str::lower(Str::random(12));
        $secret = 'htsms_whsec_'.$prefix.'_'.bin2hex(random_bytes(32));
        $endpoint = $organization->webhookEndpoints()->create([
            'name' => $name, 'url' => $url, 'events' => $events,
            'signing_secret' => $secret, 'secret_prefix' => $prefix,
        ]);

        return new IssuedWebhookSecret($endpoint, $secret);
    }

    public function disable(WebhookEndpoint $endpoint): void
    {
        $endpoint->forceFill(['is_active' => false, 'disabled_at' => now()])->save();
    }
}
