<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Messaging;

use App\Domain\Messaging\MessageStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessageStatusTest extends TestCase
{
    /**
     * @return iterable<string, array{MessageStatus, MessageStatus}>
     */
    public static function allowedTransitionProvider(): iterable
    {
        yield 'draft can queue immediately' => [MessageStatus::Draft, MessageStatus::Queued];
        yield 'scheduled becomes queued' => [MessageStatus::Scheduled, MessageStatus::Queued];
        yield 'queued is leased to a device' => [MessageStatus::Queued, MessageStatus::Assigned];
        yield 'assigned is dispatched' => [MessageStatus::Assigned, MessageStatus::Dispatched];
        yield 'dispatched is accepted by Android' => [MessageStatus::Dispatched, MessageStatus::Sent];
        yield 'sent receives delivery report' => [MessageStatus::Sent, MessageStatus::Delivered];
        yield 'temporary failure returns to queue' => [MessageStatus::RetryPending, MessageStatus::Queued];
    }

    #[DataProvider('allowedTransitionProvider')]
    public function test_it_allows_valid_transitions(MessageStatus $current, MessageStatus $next): void
    {
        self::assertTrue($current->canTransitionTo($next));
    }

    public function test_it_rejects_skipped_and_reverse_transitions(): void
    {
        self::assertFalse(MessageStatus::Queued->canTransitionTo(MessageStatus::Delivered));
        self::assertFalse(MessageStatus::Sent->canTransitionTo(MessageStatus::Queued));
    }

    public function test_terminal_statuses_cannot_transition(): void
    {
        foreach ([MessageStatus::Delivered, MessageStatus::Failed, MessageStatus::Expired, MessageStatus::Cancelled] as $status) {
            self::assertTrue($status->isTerminal());
            self::assertSame([], $status->allowedTransitions());
        }
    }
}
