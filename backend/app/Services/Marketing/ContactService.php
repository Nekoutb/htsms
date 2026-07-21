<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Domain\Marketing\ConsentStatus;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Suppression;
use Illuminate\Support\Facades\DB;

final class ContactService
{
    /** @param array<string, mixed> $data */
    public function upsert(Organization $organization, array $data): Contact
    {
        return DB::transaction(function () use ($organization, $data): Contact {
            $statusValue = $data['consent_status'] ?? null;
            if (! is_string($statusValue)) {
                throw new \InvalidArgumentException('Consent status must be a string.');
            }
            $status = ConsentStatus::from($statusValue);
            $contact = Contact::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'phone' => $data['phone']],
                [
                    'name' => $data['name'] ?? null,
                    'attributes' => $data['attributes'] ?? null,
                    'consent_status' => $status,
                    'consent_source' => $data['consent_source'] ?? null,
                    'consented_at' => $status === ConsentStatus::Consented ? now() : null,
                    'opted_out_at' => $status === ConsentStatus::OptedOut ? now() : null,
                ],
            );

            if ($status === ConsentStatus::OptedOut) {
                Suppression::query()->updateOrCreate(
                    ['organization_id' => $organization->id, 'phone' => $contact->phone],
                    ['reason' => 'contact_opt_out', 'source' => 'api', 'created_at' => now()],
                );
            }

            return $contact;
        });
    }
}
