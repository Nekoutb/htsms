<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum DashboardAbility: string
{
    case ProfileRead = 'profile:read';
    case OrganizationsRead = 'organizations:read';
    case OrganizationsWrite = 'organizations:write';
    case ApiKeysRead = 'api-keys:read';
    case ApiKeysWrite = 'api-keys:write';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $ability): string => $ability->value,
            self::cases(),
        );
    }
}
