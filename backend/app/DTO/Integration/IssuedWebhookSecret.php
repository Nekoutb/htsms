<?php

declare(strict_types=1);

namespace App\DTO\Integration;

use App\Models\WebhookEndpoint;

final readonly class IssuedWebhookSecret
{
    public function __construct(public WebhookEndpoint $endpoint, public string $plainTextSecret) {}
}
