<?php

declare(strict_types=1);

namespace App\DTO\Gateway;

use App\Models\Device;

final readonly class PairedDevice
{
    public function __construct(
        public Device $device,
        public string $plainTextCredential,
    ) {}
}
