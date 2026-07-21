<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\SubscriptionLimitException;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

final class SubscriptionService
{
    public function current(Organization $organization): Subscription
    {
        $subscription = $organization->subscription()->first();
        if ($subscription === null) {
            $subscription = $this->createTrial($organization);
        }
        if ($subscription->status === 'trialing' && $subscription->trial_ends_at?->isPast() === true) {
            $subscription->forceFill(['status' => 'expired'])->save();
        } elseif ($subscription->status === 'active' && $subscription->current_period_ends_at->isPast()) {
            $subscription->forceFill([
                'messages_used' => 0, 'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addMonth(),
            ])->save();
        }

        return $subscription->refresh();
    }

    public function createTrial(Organization $organization): Subscription
    {
        $trialDays = config('htsms.trial_days');
        $days = is_int($trialDays) ? $trialDays : 14;

        return $organization->subscription()->firstOrCreate([], [
            'plan' => 'trial', 'status' => 'trialing', 'messages_used' => 0,
            'trial_ends_at' => now()->addDays($days), 'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addDays($days),
        ]);
    }

    public function consumeMessage(Organization $organization): Subscription
    {
        return DB::transaction(function () use ($organization): Subscription {
            $subscription = $this->current($organization);
            $locked = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            $this->ensureUsable($organization, $locked);
            $limit = $this->limit($locked->plan, 'messages');
            if ($locked->messages_used >= $limit) {
                throw new SubscriptionLimitException('The message allowance for this billing period has been reached.');
            }
            $locked->increment('messages_used');

            return $locked->refresh();
        });
    }

    public function ensureDeviceAvailable(Organization $organization): void
    {
        $subscription = $this->current($organization);
        $this->ensureUsable($organization, $subscription);
        $active = $organization->devices()->whereNull('revoked_at')->count();
        if ($active >= $this->limit($subscription->plan, 'devices')) {
            throw new SubscriptionLimitException('The active device limit for this plan has been reached.');
        }
    }

    public function activate(Subscription $subscription, string $plan): Subscription
    {
        $this->limit($plan, 'messages');
        $subscription->forceFill([
            'plan' => $plan, 'status' => 'active', 'messages_used' => 0, 'trial_ends_at' => null,
            'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addMonth(), 'grace_ends_at' => null, 'cancelled_at' => null,
        ])->save();

        return $subscription->refresh();
    }

    private function ensureUsable(Organization $organization, Subscription $subscription): void
    {
        if ($organization->suspended_at !== null) {
            throw new SubscriptionLimitException('This workspace is suspended.');
        }
        if ($organization->sending_paused_at !== null) {
            throw new SubscriptionLimitException('Sending is paused for this workspace.');
        }
        if (! in_array($subscription->status, ['trialing', 'active'], true)) {
            throw new SubscriptionLimitException('An active subscription is required.');
        }
    }

    private function limit(string $plan, string $key): int
    {
        $value = config("htsms.plans.{$plan}.{$key}");
        if (! is_int($value)) {
            throw new SubscriptionLimitException('The selected plan is not configured.');
        }

        return $value;
    }
}
