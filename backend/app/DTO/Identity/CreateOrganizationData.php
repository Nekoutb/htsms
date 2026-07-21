<?php

declare(strict_types=1);

namespace App\DTO\Identity;

final readonly class CreateOrganizationData
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $timezone,
        public string $locale,
    ) {}
}
