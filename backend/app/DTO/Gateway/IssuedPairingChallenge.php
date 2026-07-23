<?php

declare(strict_types=1);

namespace App\DTO\Gateway;

use App\Models\DevicePairingChallenge;

final readonly class IssuedPairingChallenge
{
    public function __construct(
        public DevicePairingChallenge $challenge,
        public string $plainTextToken,
        public string $shortCode,
    ) {}

    /**
     * Deep link encoded in the pairing QR. Carries the server origin so the
     * gateway app connects to whichever portal issued the code instead of a
     * compile-time default.
     */
    public function pairingUri(): string
    {
        $host = config('app.url');

        return 'htsms://pair?code='.$this->shortCode.(is_string($host) && $host !== ''
            ? '&host='.rawurlencode(rtrim($host, '/'))
            : '');
    }
}
