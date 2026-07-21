<?php

declare(strict_types=1);

namespace App\DTO\Identity;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
