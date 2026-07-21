<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Marketing\ConsentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $phone
 * @property string|null $name
 * @property array<string, mixed>|null $attributes
 * @property ConsentStatus $consent_status
 * @property string|null $consent_source
 * @property CarbonImmutable|null $consented_at
 * @property CarbonImmutable|null $opted_out_at
 */
final class Contact extends Model
{
    use HasUlids;

    protected $fillable = ['organization_id', 'phone', 'name', 'attributes', 'consent_status', 'consent_source', 'consented_at', 'opted_out_at'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function casts(): array
    {
        return ['attributes' => 'array', 'consent_status' => ConsentStatus::class, 'consented_at' => 'immutable_datetime', 'opted_out_at' => 'immutable_datetime'];
    }
}
