<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A single-use, short-lived email sign-in link (magic link). Only the SHA-256
 * hash of the token is persisted; the raw token lives only in the emailed URL.
 *
 * @property string $email
 * @property string $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 * @property string|null $requested_ip
 */
final class LoginLink extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'consumed_at',
        'requested_ip',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
