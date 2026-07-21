<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class GrantPlatformAdmin extends Command
{
    protected $signature = 'admins:grant {email}';

    protected $description = 'Grant platform-administrator access to an existing verified user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', mb_strtolower($email))->first();
        if ($user === null || ! $user->hasVerifiedEmail()) {
            $this->error('A verified user with that email was not found.');

            return self::FAILURE;
        }
        $user->forceFill(['is_platform_admin' => true])->save();
        $this->info('Platform administrator access granted.');

        return self::SUCCESS;
    }
}
