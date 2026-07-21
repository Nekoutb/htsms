<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Domain\Messaging\MessageStatus;
use App\Models\Device;
use App\Models\Message;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MessageMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_schedules_promote_and_expired_messages_expire(): void
    {
        $organization = Organization::factory()->create();
        $due = $this->message($organization, MessageStatus::Scheduled, ['scheduled_at' => now()->subMinute()]);
        $future = $this->message($organization, MessageStatus::Scheduled, ['scheduled_at' => now()->addHour()]);
        $expired = $this->message($organization, MessageStatus::Queued, ['expires_at' => now()->subSecond()]);

        $this->artisan('messages:maintain')->assertSuccessful();

        self::assertSame(MessageStatus::Queued, $due->refresh()->status);
        self::assertSame(MessageStatus::Scheduled, $future->refresh()->status);
        self::assertSame(MessageStatus::Expired, $expired->refresh()->status);
    }

    public function test_abandoned_assignment_requeues_but_uncertain_dispatch_never_duplicates(): void
    {
        $organization = Organization::factory()->create();
        $device = Device::factory()->for($organization)->create();
        $assigned = $this->message($organization, MessageStatus::Assigned, [
            'device_id' => $device->id, 'assigned_at' => now()->subMinutes(3),
        ]);
        $dispatched = $this->message($organization, MessageStatus::Dispatched, [
            'device_id' => $device->id, 'assigned_at' => now()->subMinutes(15),
        ]);
        $dispatched->forceFill(['updated_at' => now()->subMinutes(11)])->save();

        $this->artisan('messages:maintain')->assertSuccessful();

        self::assertSame(MessageStatus::Queued, $assigned->refresh()->status);
        self::assertNull($assigned->device_id);
        self::assertSame(2, $assigned->events()->count());
        self::assertSame(MessageStatus::Failed, $dispatched->refresh()->status);
        self::assertSame('delivery_unknown', $dispatched->failure_code);
    }

    /** @param array<string, mixed> $overrides */
    private function message(Organization $organization, MessageStatus $status, array $overrides = []): Message
    {
        return Message::query()->create([
            'organization_id' => $organization->id,
            'recipient' => '+237670000003',
            'body' => 'Maintenance test',
            'status' => $status,
            ...$overrides,
        ]);
    }
}
