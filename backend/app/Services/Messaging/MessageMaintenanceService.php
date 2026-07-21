<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Domain\Messaging\MessageStatus;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

final readonly class MessageMaintenanceService
{
    public function __construct(private MessageTransitionService $transitions) {}

    /** @return array{promoted: int, expired: int, recovered: int, uncertain: int} */
    public function run(): array
    {
        return DB::transaction(function (): array {
            $promoted = $this->promoteScheduled();
            $expired = $this->expireMessages();
            $recovered = $this->recoverAssigned();
            $uncertain = $this->failUncertainDispatches();

            return compact('promoted', 'expired', 'recovered', 'uncertain');
        });
    }

    private function promoteScheduled(): int
    {
        $messages = Message::query()->where('status', MessageStatus::Scheduled)
            ->where('scheduled_at', '<=', now())->lockForUpdate()->get();
        foreach ($messages as $message) {
            $this->transitions->transition($message, MessageStatus::Queued, 'scheduler');
        }

        return $messages->count();
    }

    private function expireMessages(): int
    {
        $messages = Message::query()
            ->whereIn('status', [MessageStatus::Scheduled, MessageStatus::Queued, MessageStatus::Assigned, MessageStatus::Dispatched, MessageStatus::RetryPending])
            ->whereNotNull('expires_at')->where('expires_at', '<=', now())->lockForUpdate()->get();
        foreach ($messages as $message) {
            $this->transitions->transition($message, MessageStatus::Expired, 'scheduler');
        }

        return $messages->count();
    }

    private function recoverAssigned(): int
    {
        $messages = Message::query()->where('status', MessageStatus::Assigned)
            ->where('assigned_at', '<=', now()->subMinutes(2))->lockForUpdate()->get();
        foreach ($messages as $message) {
            $this->transitions->transition($message, MessageStatus::RetryPending, 'scheduler', ['reason' => 'lease_timeout']);
            $message->forceFill(['device_id' => null, 'device_sim_slot_id' => null, 'assigned_at' => null])->save();
            $this->transitions->transition($message, MessageStatus::Queued, 'scheduler');
        }

        return $messages->count();
    }

    private function failUncertainDispatches(): int
    {
        $messages = Message::query()->where('status', MessageStatus::Dispatched)
            ->where('updated_at', '<=', now()->subMinutes(10))->lockForUpdate()->get();
        foreach ($messages as $message) {
            $message->forceFill([
                'failure_code' => 'delivery_unknown',
                'failure_message' => 'The phone did not acknowledge the carrier result. The message was not retried to prevent a duplicate SMS.',
            ])->save();
            $this->transitions->transition($message, MessageStatus::Failed, 'scheduler', ['reason' => 'dispatch_ack_timeout']);
        }

        return $messages->count();
    }
}
