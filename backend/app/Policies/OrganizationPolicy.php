<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;

final class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $this->membership($user, $organization) !== null;
    }

    public function manageApiKeys(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user, $organization);

        return in_array($membership?->role, [
            OrganizationRole::Owner,
            OrganizationRole::Administrator,
        ], true);
    }

    public function manageDevices(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user, $organization);

        return $membership?->role?->canManageMembers() === true;
    }

    public function manageMarketing(User $user, Organization $organization): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        $membership = $this->membership($user, $organization);

        return in_array($membership?->role, [
            OrganizationRole::Owner,
            OrganizationRole::Administrator,
            OrganizationRole::CampaignManager,
        ], true);
    }

    private function membership(User $user, Organization $organization): ?OrganizationMembership
    {
        return OrganizationMembership::query()
            ->whereBelongsTo($organization)
            ->whereBelongsTo($user)
            ->first();
    }
}
