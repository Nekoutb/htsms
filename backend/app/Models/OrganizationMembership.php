<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Identity\OrganizationRole;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationMembership extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'joined_at' => 'immutable_datetime',
        ];
    }
}
