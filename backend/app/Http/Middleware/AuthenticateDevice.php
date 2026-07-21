<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\DeviceCredential;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateDevice
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextCredential = $request->bearerToken() ?? $request->header('X-Device-Key');
        if (! is_string($plainTextCredential) || ! str_starts_with($plainTextCredential, 'htsms_device_')) {
            return $this->unauthorized();
        }

        $credential = DeviceCredential::query()
            ->with('device.organization')
            ->where('secret_hash', hash('sha256', $plainTextCredential))
            ->whereNull('revoked_at')
            ->first();

        if ($credential === null || $credential->device->revoked_at !== null) {
            return $this->unauthorized();
        }

        $credential->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('device', $credential->device);
        $request->attributes->set('organization', $credential->device->organization);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Invalid or revoked device credential.'], 401);
    }
}
