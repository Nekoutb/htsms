<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Device;
use App\Models\OrganizationMembership;
use App\Models\User;

final class DevicePolicy
{
    public function view(User $user, Device $device): bool
    {
        return OrganizationMembership::query()
            ->where('organization_id', $device->organization_id)
            ->whereBelongsTo($user)
            ->exists();
    }

    public function revoke(User $user, Device $device): bool
    {
        $membership = OrganizationMembership::query()
            ->where('organization_id', $device->organization_id)
            ->whereBelongsTo($user)
            ->first();

        return $membership?->role?->canManageMembers() === true;
    }
}
