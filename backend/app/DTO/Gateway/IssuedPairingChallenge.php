<?php

declare(strict_types=1);

namespace App\DTO\Gateway;

use App\Models\DevicePairingChallenge;

final readonly class IssuedPairingChallenge
{
    public function __construct(
        public DevicePairingChallenge $challenge,
        public string $plainTextToken,
    ) {}
}
