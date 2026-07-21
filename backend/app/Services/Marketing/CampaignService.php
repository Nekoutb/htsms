<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Domain\Marketing\CampaignStatus;
use App\Domain\Marketing\ConsentStatus;
use App\Jobs\QueueCampaignRecipient;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Suppression;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class CampaignService
{
    /** @param list<string> $contactIds */
    public function create(Organization $organization, string $name, string $content, array $contactIds, ?DateTimeInterface $sendAt): Campaign
    {
        $campaign = DB::transaction(function () use ($organization, $name, $content, $contactIds, $sendAt): Campaign {
            $contacts = Contact::query()->whereBelongsTo($organization)->whereIn('id', $contactIds)->get();
            abort_if($contacts->count() !== count($contactIds), 422, 'One or more contacts are invalid.');
            $suppressed = Suppression::query()->where('organization_id', $organization->id)
                ->whereIn('phone', $contacts->pluck('phone'))->pluck('phone')->flip();

            $campaign = $organization->campaigns()->create([
                'name' => $name, 'content' => $content, 'status' => CampaignStatus::Processing,
                'scheduled_at' => $sendAt, 'launched_at' => now(),
            ]);

            $eligible = 0;
            $blocked = 0;
            foreach ($contacts as $contact) {
                $reason = $contact->consent_status !== ConsentStatus::Consented ? 'consent_required' : ($suppressed->has($contact->phone) ? 'suppressed' : null);
                CampaignRecipient::query()->create([
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id, 'phone' => $contact->phone,
                    'status' => $reason === null ? 'pending' : 'suppressed', 'reason' => $reason,
                ]);
                $reason === null ? $eligible++ : $blocked++;
            }
            $campaign->update(['recipient_count' => $eligible, 'suppressed_count' => $blocked]);

            return $campaign;
        });

        foreach ($campaign->recipients()->where('status', 'pending')->get(['id']) as $recipient) {
            QueueCampaignRecipient::dispatch($recipient->id);
        }

        return $campaign->refresh();
    }
}
