<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Domain\Marketing\ConsentStatus;
use App\Models\Contact;
use App\Models\InboundMessage;
use App\Models\Suppression;
use Illuminate\Support\Facades\DB;

final class InboundOptOutService
{
    /** @var list<string> */
    private const KEYWORDS = ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT', 'ARRET', 'ARRÊT'];

    public function process(InboundMessage $message): bool
    {
        $keyword = mb_strtoupper(trim($message->body, " \t\n\r\0\x0B.!"));
        if (! in_array($keyword, self::KEYWORDS, true)) {
            return false;
        }

        DB::transaction(function () use ($message): void {
            Suppression::query()->updateOrCreate(
                ['organization_id' => $message->organization_id, 'phone' => $message->sender],
                ['reason' => 'inbound_opt_out', 'source' => 'sms_keyword', 'created_at' => now()],
            );
            Contact::query()->where('organization_id', $message->organization_id)
                ->where('phone', $message->sender)
                ->update([
                    'consent_status' => ConsentStatus::OptedOut,
                    'opted_out_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return true;
    }
}
