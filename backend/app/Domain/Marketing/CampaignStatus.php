<?php

declare(strict_types=1);

namespace App\Domain\Marketing;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Queued = 'queued';
    case Failed = 'failed';
}
