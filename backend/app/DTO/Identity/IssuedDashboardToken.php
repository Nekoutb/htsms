<?php

declare(strict_types=1);

namespace App\DTO\Identity;

use App\Models\User;

final readonly class IssuedDashboardToken
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
