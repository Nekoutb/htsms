<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Domain\Identity\OrganizationRole;
use App\DTO\Identity\CreateOrganizationData;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class OrganizationService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function createForOwner(CreateOrganizationData $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner): Organization {
            $organization = Organization::query()->create([
                'name' => $data->name,
                'slug' => $data->slug ?? $this->uniqueSlug($data->name),
                'timezone' => $data->timezone,
                'locale' => $data->locale,
            ]);

            $organization->memberships()->create([
                'user_id' => $owner->getKey(),
                'role' => OrganizationRole::Owner,
                'joined_at' => now(),
            ]);
            $this->subscriptions->createTrial($organization);

            return $organization;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name), 60, '');
        if ($base === '') {
            $base = 'workspace';
        }
        $slug = $base;
        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
