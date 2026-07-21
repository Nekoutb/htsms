<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlatformAdmin
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof User && $request->user()->is_platform_admin, Response::HTTP_FORBIDDEN);
        $verifiedAt = $request->session()->get('platform_admin_mfa_verified_at');
        $verifiedUserId = $request->session()->get('platform_admin_mfa_user_id');
        if (! is_int($verifiedAt) || $verifiedAt < now()->subHours(8)->getTimestamp() || $verifiedUserId !== $request->user()->getKey()) {
            return redirect()->route('admin.mfa.show');
        }

        return $next($request);
    }
}
