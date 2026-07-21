<?php

declare(strict_types=1);

namespace App\DTO\Integration;

use App\Models\DeveloperApiKey;

final readonly class IssuedApiKey
{
    public function __construct(
        public DeveloperApiKey $apiKey,
        public string $plainTextKey,
    ) {}
}
