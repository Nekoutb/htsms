<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Identity\MagicLinkService;
use Illuminate\Console\Command;

/**
 * Operator safety net: mint a working sign-in link for an existing account
 * without sending email. Ensures an administrator can never be locked out if
 * mail delivery is unavailable.
 */
final class IssueMagicLink extends Command
{
    protected $signature = 'auth:magic-link {email}';

    protected $description = 'Print a single-use sign-in link for an existing account';

    public function handle(MagicLinkService $magicLinks): int
    {
        $email = (string) $this->argument('email');
        $url = $magicLinks->issueUrl($email);

        if ($url === null) {
            $this->error('No account exists for that email.');

            return self::FAILURE;
        }

        $this->info('Single-use sign-in link (valid 15 minutes):');
        $this->line($url);

        return self::SUCCESS;
    }
}
