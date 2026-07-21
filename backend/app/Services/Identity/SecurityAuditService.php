<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Domain\Identity\SecurityEvent;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use RuntimeException;

final readonly class SecurityAuditService
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function record(SecurityEvent $event, Request $request, ?User $user = null, array $metadata = []): void
    {
        SecurityAuditEvent::query()->create([
            'user_id' => $user?->getKey(),
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function emailFingerprint(string $email): string
    {
        $applicationKey = config('app.key');
        if (! is_string($applicationKey) || $applicationKey === '') {
            throw new RuntimeException('APP_KEY must be configured.');
        }

        return hash_hmac('sha256', mb_strtolower(trim($email)), $applicationKey);
    }
}
