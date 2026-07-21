<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\DeveloperApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateDeveloperApiKey
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextKey = $request->bearerToken() ?? $request->header('X-API-Key');

        if (! is_string($plainTextKey) || ! str_starts_with($plainTextKey, 'htsms_live_')) {
            return $this->unauthorized();
        }

        $apiKey = DeveloperApiKey::query()
            ->with('organization')
            ->where('secret_hash', hash('sha256', $plainTextKey))
            ->first();

        if ($apiKey === null || ! $apiKey->isUsable()) {
            return $this->unauthorized();
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('developer_api_key', $apiKey);
        $request->attributes->set('organization', $apiKey->organization);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid or expired API key.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
