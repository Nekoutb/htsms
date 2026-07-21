<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string|null $contact_id
 * @property string|null $message_id
 * @property string $phone
 * @property string $status
 * @property Campaign $campaign
 */
final class CampaignRecipient extends Model
{
    use HasUlids;

    protected $fillable = ['campaign_id', 'contact_id', 'message_id', 'phone', 'status', 'reason'];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
