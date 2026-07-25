<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\DTO\Integration\CreateApiKeyData;
use App\DTO\Integration\IssuedApiKey;
use App\Models\DeveloperApiKey;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DeveloperApiKeyService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function issue(Organization $organization, User $creator, CreateApiKeyData $data): IssuedApiKey
    {
        return DB::transaction(function () use ($organization, $creator, $data): IssuedApiKey {
            $this->subscriptions->ensureApiKeyAvailable($organization);
            $prefix = Str::lower(Str::random(12));
            $secret = bin2hex(random_bytes(32));
            $plainTextKey = "htsms_live_{$prefix}_{$secret}";

            $apiKey = $organization->developerApiKeys()->create([
                'created_by_user_id' => $creator->getKey(),
                'name' => $data->name,
                'prefix' => $prefix,
                'secret_hash' => hash('sha256', $plainTextKey),
                'abilities' => $data->abilities,
                'expires_at' => $data->expiresAt,
            ]);

            return new IssuedApiKey($apiKey, $plainTextKey);
        });
    }

    public function revoke(DeveloperApiKey $apiKey): void
    {
        if ($apiKey->revoked_at === null) {
            $apiKey->forceFill(['revoked_at' => now()])->save();
        }
    }

    public function rotate(DeveloperApiKey $apiKey, User $actor): IssuedApiKey
    {
        return DB::transaction(function () use ($apiKey, $actor): IssuedApiKey {
            $locked = DeveloperApiKey::query()
                ->whereKey($apiKey->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->revoke($locked);

            return $this->issue(
                $locked->organization,
                $actor,
                new CreateApiKeyData(
                    name: $locked->name,
                    abilities: $locked->abilities,
                    expiresAt: $locked->expires_at,
                ),
            );
        });
    }
}
