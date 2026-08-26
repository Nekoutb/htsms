<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\LoginLink;
use App\Models\User;
use App\Notifications\LoginLinkNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Issues and consumes single-use email sign-in links (magic links).
 *
 * Security policy:
 *  - Tokens are 256 bits of CSPRNG entropy; only their SHA-256 hash is stored.
 *  - Links expire after a short window and can be used exactly once.
 *  - Requesting a new link invalidates any earlier unused link for the email.
 *  - Requests for unknown emails do nothing, so callers can respond identically
 *    whether or not an account exists (no user enumeration).
 *  - The raw token is never written to logs; it exists only in the emailed URL.
 */
final class MagicLinkService
{
    /** How long an issued link remains valid. */
    private const TTL_MINUTES = 15;

    /**
     * Issue a sign-in link for the email when a matching account exists.
     * Silently does nothing otherwise so callers cannot leak account existence.
     */
    public function sendTo(string $email, ?Request $request = null): void
    {
        $email = mb_strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return;
        }

        // Invalidate any earlier unused links for this address.
        LoginLink::query()->where('email', $email)->whereNull('consumed_at')->delete();

        $rawToken = bin2hex(random_bytes(32));
        LoginLink::query()->create([
            'email' => $email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'requested_ip' => $request?->ip(),
        ]);

        $user->notify(new LoginLinkNotification($this->urlFor($rawToken), self::TTL_MINUTES));
    }

    /**
     * Build the absolute sign-in URL for a raw token. Exposed so the console
     * safety-net command can print a working link without sending email.
     */
    public function issueUrl(string $email): ?string
    {
        $email = mb_strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return null;
        }

        LoginLink::query()->where('email', $email)->whereNull('consumed_at')->delete();
        $rawToken = bin2hex(random_bytes(32));
        LoginLink::query()->create([
            'email' => $email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $this->urlFor($rawToken);
    }

    /**
     * Atomically consume a raw token and return the associated user, or null
     * when the token is unknown, expired, or already used.
     */
    public function consume(string $rawToken): ?User
    {
        $hash = hash('sha256', $rawToken);

        return DB::transaction(function () use ($hash): ?User {
            $link = LoginLink::query()
                ->where('token_hash', $hash)
                ->whereNull('consumed_at')
                ->where('expires_at', '>=', now())
                ->lockForUpdate()
                ->first();

            if ($link === null) {
                return null;
            }

            $link->forceFill(['consumed_at' => now()])->save();

            $user = User::query()->where('email', $link->email)->first();
            if ($user === null) {
                return null;
            }

            if (! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        });
    }

    private function urlFor(string $rawToken): string
    {
        return URL::route('magic.verify', ['token' => $rawToken]);
    }
}
