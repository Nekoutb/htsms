<?php

declare(strict_types=1);

namespace App\Domain\Marketing;

enum ConsentStatus: string
{
    case Unknown = 'unknown';
    case Consented = 'consented';
    case OptedOut = 'opted_out';
}
