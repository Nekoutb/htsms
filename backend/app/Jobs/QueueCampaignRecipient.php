<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Marketing\CampaignStatus;
use App\Models\CampaignRecipient;
use App\Models\Organization;
use App\Services\Messaging\MessageSubmissionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class QueueCampaignRecipient implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $recipientId) {}

    public function handle(MessageSubmissionService $submissions): void
    {
        $recipient = CampaignRecipient::query()->with('campaign.organization')->find($this->recipientId);
        if ($recipient === null || $recipient->status !== 'pending') {
            return;
        }
        $campaign = $recipient->campaign;
        $organization = $campaign->organization;
        if (! $organization instanceof Organization) {
            throw new \LogicException('Campaign organization is missing.');
        }
        $submitted = $submissions->submit(
            $organization, $recipient->phone, $campaign->content,
            $campaign->scheduled_at, null, 'campaign:'.$recipient->id, 'campaign',
        );
        $recipient->update(['message_id' => $submitted->message->id, 'status' => 'queued']);
        if (! $campaign->recipients()->where('status', 'pending')->exists()) {
            $campaign->update(['status' => CampaignStatus::Queued]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $recipient = CampaignRecipient::query()->with('campaign')->find($this->recipientId);
        if ($recipient === null) {
            return;
        }
        $recipient->update(['status' => 'failed', 'reason' => 'queue_failed']);
        $recipient->campaign->update(['status' => CampaignStatus::Failed]);
    }
}
