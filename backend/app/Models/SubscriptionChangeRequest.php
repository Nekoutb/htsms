<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $id @property string $organization_id @property int $requested_by_user_id @property int|null $reviewed_by_user_id @property string $requested_plan @property string $status @property string|null $customer_note @property string|null $admin_note @property CarbonImmutable|null $reviewed_at */
final class SubscriptionChangeRequest extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['organization_id', 'requested_by_user_id', 'reviewed_by_user_id', 'requested_plan', 'status', 'customer_note', 'admin_note', 'reviewed_at'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime'];
    }
}
