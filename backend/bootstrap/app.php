<?php

use App\Exceptions\SubscriptionLimitException;
use App\Http\Middleware\AuthenticateDeveloperApiKey;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\RequireDeveloperApiKeyAbility;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [SetLocale::class]);
        $middleware->redirectUsersTo(fn (): string => route('portal.home'));
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'developer-api-key' => AuthenticateDeveloperApiKey::class,
            'developer-ability' => RequireDeveloperApiKeyAbility::class,
            'device-auth' => AuthenticateDevice::class,
            'platform-admin' => RequirePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (SubscriptionLimitException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 402);
            }

            return back()->withErrors(['subscription' => $exception->getMessage()]);
        });
        // A stale form (expired session) should land on the sign-in page with
        // an explanation, never on a bare "Page Expired" error screen.
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please sign in again.'], 419);
            }

            return redirect()->route('login')->with('status', __('Your session expired. Please sign in again.'));
        });
    })->create();
