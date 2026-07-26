<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->user()?->organizations()->value('locale') ?? 'en');
        app()->setLocale(in_array($locale, ['en', 'fr'], true) ? $locale : 'en');

        return $next($request);
    }
}
