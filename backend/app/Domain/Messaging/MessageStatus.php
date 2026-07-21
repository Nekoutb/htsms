<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

enum MessageStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Queued = 'queued';
    case Assigned = 'assigned';
    case Dispatched = 'dispatched';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case RetryPending = 'retry_pending';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Failed, self::Expired, self::Cancelled => true,
            default => false,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Scheduled, self::Queued, self::Cancelled],
            self::Scheduled => [self::Queued, self::Expired, self::Cancelled],
            self::Queued => [self::Assigned, self::Expired, self::Cancelled],
            self::Assigned => [self::Dispatched, self::RetryPending, self::Failed, self::Expired],
            self::Dispatched => [self::Sent, self::RetryPending, self::Failed, self::Expired],
            self::Sent => [self::Delivered, self::Failed],
            self::RetryPending => [self::Queued, self::Failed, self::Expired, self::Cancelled],
            self::Delivered, self::Failed, self::Expired, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
