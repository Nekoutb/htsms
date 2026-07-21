<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\DeveloperApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireDeveloperApiKeyAbility
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $apiKey = $request->attributes->get('developer_api_key');

        if (! $apiKey instanceof DeveloperApiKey || ! in_array($ability, $apiKey->abilities, true)) {
            return new JsonResponse([
                'message' => 'API key does not have the required ability.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
