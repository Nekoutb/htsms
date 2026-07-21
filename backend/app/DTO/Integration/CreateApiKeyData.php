<?php

declare(strict_types=1);

namespace App\DTO\Integration;

use DateTimeImmutable;

final readonly class CreateApiKeyData
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public string $name,
        public array $abilities,
        public ?DateTimeImmutable $expiresAt,
    ) {}
}
