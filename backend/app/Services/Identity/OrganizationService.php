<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Domain\Identity\OrganizationRole;
use App\DTO\Identity\CreateOrganizationData;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class OrganizationService
{
    public function createForOwner(CreateOrganizationData $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner): Organization {
            $organization = Organization::query()->create([
                'name' => $data->name,
                'slug' => $data->slug,
                'timezone' => $data->timezone,
                'locale' => $data->locale,
            ]);

            $organization->memberships()->create([
                'user_id' => $owner->getKey(),
                'role' => OrganizationRole::Owner,
                'joined_at' => now(),
            ]);

            return $organization;
        });
    }
}
