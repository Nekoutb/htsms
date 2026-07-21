<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Domain\Identity\DashboardAbility;
use App\DTO\Identity\IssuedDashboardToken;
use App\DTO\Identity\LoginData;
use App\DTO\Identity\RegisterUserData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

final readonly class AuthenticationService
{
    public function register(RegisterUserData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => mb_strtolower($data->email),
            'password' => $data->password,
        ]);
    }

    public function authenticate(LoginData $data): IssuedDashboardToken
    {
        $user = User::query()->where('email', mb_strtolower($data->email))->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('The supplied credentials are invalid.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('The supplied credentials are invalid.');
        }

        Hash::needsRehash($user->password) && $user->forceFill([
            'password' => Hash::make($data->password),
        ])->save();

        $token = $user->createToken(
            $data->deviceName,
            DashboardAbility::values(),
            now()->addDays(30),
        );

        return new IssuedDashboardToken($user, $token->plainTextToken);
    }
}
