<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Developer = 'developer';
    case CampaignManager = 'campaign_manager';
    case Viewer = 'viewer';

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Administrator => true,
            default => false,
        };
    }

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }
}
