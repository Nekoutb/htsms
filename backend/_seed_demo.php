<?php

use App\Domain\Identity\OrganizationRole;
use App\Domain\Messaging\MessageStatus;
use App\Models\Device;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;

$u = User::firstOrCreate(['email' => 'demo@htsms.test'], ['name' => 'Amina Demo', 'password' => bcrypt('Correct-Horse-99!'), 'email_verified_at' => now()]);
$o = Organization::firstOrCreate(['slug' => 'douala-traders'], ['name' => 'Douala Traders', 'timezone' => 'Africa/Douala', 'locale' => 'en']);
$o->memberships()->firstOrCreate(['user_id' => $u->id], ['role' => OrganizationRole::Owner, 'joined_at' => now()]);
app(SubscriptionService::class)->createTrial($o);

$d = Device::firstOrCreate(['organization_id' => $o->id, 'name' => 'Shop Counter Phone'], ['manufacturer' => 'Tecno', 'model' => 'Spark 20', 'android_version' => '14', 'app_version' => '0.3.0', 'battery_percent' => 76, 'connection_type' => 'wifi', 'last_seen_at' => now()]);
$d->simSlots()->firstOrCreate(['slot_index' => 0], ['carrier_name' => 'MTN Cameroon', 'phone_number' => '+237670001234', 'is_active' => true]);
$d->simSlots()->firstOrCreate(['slot_index' => 1], ['carrier_name' => 'Orange CM', 'phone_number' => null, 'is_active' => true]);

$rows = [
    ['+237690111222', 'Your order #1001 is ready for pickup.', MessageStatus::Delivered],
    ['+237655333444', 'Reminder: appointment tomorrow 10am.', MessageStatus::Sent],
    ['+237699888777', 'Your OTP is 448120.', MessageStatus::Queued],
];
foreach ($rows as $m) {
    Message::firstOrCreate(['organization_id' => $o->id, 'recipient' => $m[0], 'body' => $m[1]], ['status' => $m[2]]);
}
$o->inboundMessages()->firstOrCreate(['device_event_id' => 'seed-1'], ['device_id' => $d->id, 'sender' => '+237690111222', 'body' => 'Yes I will come at noon, thank you', 'received_at' => now()->subMinutes(6)]);
$o->inboundMessages()->firstOrCreate(['device_event_id' => 'seed-2'], ['device_id' => $d->id, 'sender' => '+237655333444', 'body' => 'STOP', 'received_at' => now()->subHours(2)]);

echo 'seeded org '.$o->id."\n";
