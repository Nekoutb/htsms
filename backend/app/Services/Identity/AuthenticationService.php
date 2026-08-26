<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\DTO\Identity\RegisterUserData;
use App\Models\User;

final readonly class AuthenticationService
{
    /**
     * Create a passwordless account. Authentication happens later through a
     * single-use email sign-in link issued by the MagicLinkService.
     */
    public function register(RegisterUserData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => mb_strtolower($data->email),
        ]);
    }
}
